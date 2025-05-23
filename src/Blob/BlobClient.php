<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Blob;

use AzurePhp\Storage\Blob\Model\Blob;
use AzurePhp\Storage\Blob\Model\BlobProperties;
use AzurePhp\Storage\Blob\Model\BlobUpload;
use AzurePhp\Storage\Blob\Model\Tags;
use AzurePhp\Storage\Blob\Model\UploadBlock;
use AzurePhp\Storage\Blob\Model\UploadBlockList;
use AzurePhp\Storage\Common\Model\Metadata as ModelMetadata;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Query;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Pool;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\UriInterface;

final readonly class BlobClient
{
    public function __construct(
        private ClientInterface $client,
        private UriInterface $uri
    ) {}

    /**
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/put-blob
     */
    public function upload(BlobUpload $blob): void
    {
        if (null === $blob->stream->getSize() || false === $blob->stream->isSeekable()) {
            $this->uploadBlocks($blob);

            return;
        }

        if ($blob->stream->getSize() > $blob->initialTransferSize) {
            $this->uploadAsync($blob);

            return;
        }

        $request = (new Request('PUT', $this->uri))->withBody($blob->stream)
            ->withHeader('x-ms-blob-type', 'BlockBlob')
            ->withHeader('content-length', (string) $blob->stream->getSize())
            ->withHeader('content-type', $blob->contentType)
        ;

        $this->client->send($request);
    }

    private function uploadBlocks(BlobUpload $blob): void
    {
        $blocks = new UploadBlockList();
        $context = hash_init('md5');

        while (true) {
            $contents = $blob->stream->read($blob->maximumTransferSize);

            if ('' === $contents) {
                break;
            }

            $block = new UploadBlock(Utils::streamFor($contents), $blocks->count());
            $blocks->push($block);

            hash_update($context, $contents);

            $this->putBlockAsync($block)->wait();
        }

        $contentMd5 = hash_final($context, true);

        $this->putBlockList($blocks, $blob->contentType, $contentMd5);
    }

    private function uploadAsync(BlobUpload $blob): void
    {
        $blocks = new UploadBlockList();

        $generator = function () use ($blob, $blocks) {
            while (true) {
                $contents = $blob->stream->read($blob->maximumTransferSize);

                if ('' === $contents) {
                    break;
                }

                $block = new UploadBlock(Utils::streamFor($contents), $blocks->count());
                $blocks->push($block);

                yield fn () => $this->putBlockAsync($block);
            }
        };

        $pool = new Pool($this->client, $generator(), ['concurrency' => $blob->maximumConcurrency]);
        $pool->promise()->wait();

        $contentMd5 = Utils::hash($blob->stream, 'md5', true);

        $this->putBlockList($blocks, $blob->contentType, $contentMd5);
    }

    private function putBlockAsync(UploadBlock $block): PromiseInterface
    {
        $query = ['comp' => 'block', 'blockid' => $block->getId()];
        $uri = $this->uri->withQuery(Query::build($query));
        $request = (new Request('PUT', $uri))
            ->withHeader('content-length', (string) $block->contents->getSize())
            ->withBody($block->contents)
        ;

        return $this->client->sendAsync($request);
    }

    private function putBlockList(UploadBlockList $blocks, string $contentType, string $contentMd5): void
    {
        $query = ['comp' => 'blocklist'];
        $uri = $this->uri->withQuery(Query::build($query));
        $body = Utils::streamFor($blocks->toXml()->asXML());
        $request = (new Request('PUT', $uri))
            ->withHeader('x-ms-blob-content-type', $contentType)
            ->withHeader('x-ms-blob-content-md5', base64_encode($contentMd5))
            ->withBody($body)
        ;

        $this->client->send($request);
    }

    public function getProperties(): BlobProperties
    {
        $request = new Request('HEAD', $this->uri);
        $response = $this->client->send($request);

        return BlobProperties::fromResponse($response);
    }

    public function exists(): bool
    {
        try {
            $this->getProperties();
        } catch (\Throwable $e) {
            if (false === $e instanceof RequestException || null === $response = $e->getResponse()) {
                throw $e;
            }

            if (404 === $response->getStatusCode()) {
                return false;
            }

            throw $e;
        }

        return true;
    }

    public function delete(): void
    {
        $this->client->send(new Request('DELETE', $this->uri));
    }

    public function download(): Blob
    {
        $request = new Request('GET', $this->uri);
        $response = $this->client->send($request, ['stream' => true]);

        $parts = explode('/', $this->uri->getPath());
        $name = end($parts);

        return new Blob($name, BlobProperties::fromResponse($response), $response->getBody());
    }

    public function setMetadata(ModelMetadata $metadata): void
    {
        $query = ['comp' => 'metadata'];
        $uri = $this->uri->withQuery(Query::build($query));
        $request = new Request('PUT', $uri);

        foreach ($metadata->toHeaders() as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        $this->client->send($request);
    }

    public function copy(UriInterface $source): void
    {
        $request = (new Request('PUT', $this->uri))->withHeader('x-ms-copy-source', (string) $source);

        $this->client->send($request);
    }

    public function setTags(Tags $tags): void
    {
        $query = ['comp' => 'tags'];
        $uri = $this->uri->withQuery(Query::build($query));
        $body = Utils::streamFor($tags->toXml()->asXML());
        $request = (new Request('PUT', $uri))->withBody($body);

        $this->client->send($request);
    }

    public function getTags(): Tags
    {
        $query = ['comp' => 'tags'];
        $uri = $this->uri->withQuery(Query::build($query));
        $request = new Request('GET', $uri);

        $response = $this->client->send($request);
        $xml = new \SimpleXMLElement($response->getBody()->getContents());

        return Tags::fromXml($xml);
    }
}

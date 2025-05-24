<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Queue;

use AzurePhp\Storage\Common\Model\Metadata;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Query;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\UriInterface;

final readonly class QueueClient
{
    public function __construct(
        private ClientInterface $client,
        private UriInterface $uri
    ) {}

    public function getMessageClient(): MessageClient
    {
        $path = rtrim($this->uri->getPath(), '/').'/messages';
        $uri = $this->uri->withPath($path);

        return new MessageClient($this->client, $uri);
    }

    /**
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/create-queue4
     */
    public function create(): void
    {
        $this->client->send(new Request('PUT', $this->uri));
    }

    public function exists(): bool
    {
        $uri = $this->uri->withQuery(Query::build(['comp' => 'metadata']));

        try {
            $response = $this->client->send(new Request('HEAD', $uri));
        } catch (\Throwable $e) {
            if (false === $e instanceof RequestException || null === $response = $e->getResponse()) {
                throw $e;
            }

            if (404 === $response->getStatusCode()) {
                return false;
            }

            throw $e;
        }

        return 200 === $response->getStatusCode();
    }

    /**
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/delete-queue3
     */
    public function delete(): void
    {
        $this->client->send(new Request('DELETE', $this->uri));
    }

    /**
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/get-queue-metadata#request
     */
    public function getMetadata(): Metadata
    {
        $uri = $this->uri->withQuery(Query::build(['comp' => 'metadata']));
        $request = new Request('GET', $uri);
        $response = $this->client->send($request);

        return Metadata::fromHeaders($response->getHeaders());
    }

    /**
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/set-queue-metadata
     */
    public function setMetadata(Metadata $metadata): void
    {
        $uri = $this->uri->withQuery(Query::build(['comp' => 'metadata']));
        $request = new Request('PUT', $uri);

        foreach ($metadata->toHeaders() as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        $this->client->send($request);

    }
}

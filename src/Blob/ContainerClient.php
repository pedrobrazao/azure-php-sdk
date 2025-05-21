<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Blob;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Query;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\UriInterface;

final readonly class ContainerClient
{
    public function __construct(
        private ClientInterface $client,
        private UriInterface $uri
    ) {}

    public function getBlobClient(string $blobName): BlobClient
    {
        $path = sprintf('%s/%s', rtrim($this->uri->getPath(), '/'), trim($blobName, '/'));
        $uri = $this->uri->withPath($path);

        return new BlobClient($this->client, $uri);
    }

    /**
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/create-container?tabs=microsoft-entra-id
     */
    public function create(): void
    {
        $query = ['restype' => 'container'];
        $uri = $this->uri->withQuery(Query::build($query));

        $this->client->send(new Request('PUT', $uri));
    }

    public function exists(): bool
    {
        $query = ['restype' => 'container'];
        $uri = $this->uri->withQuery(Query::build($query));

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
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/delete-container
     */
    public function delete(): void
    {
        $query = ['restype' => 'container'];
        $uri = $this->uri->withQuery(Query::build($query));

        $this->client->send(new Request('DELETE', $uri));
    }
}

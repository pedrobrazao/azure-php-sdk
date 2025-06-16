<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Blob;

use AzurePhp\Storage\Common\Client\AbstractClient;
use AzurePhp\Storage\Blob\Auth\SharedAccessKey;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Query;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

final readonly class BlobClient extends AbstractClient
{
    /**
     * @param array<callable|scalar|scalar[]> $options
     */
    public static function create(string $connectionString, array $options = []): self
    {
        return self::factory($connectionString, 'blob', [], SharedAccessKey::class, $options);
    }

    /**
     * @param array<string, string> $params
     */
    private function uriForContainer(string $containerName, array $params = []): UriInterface
    {
        $path = sprintf('%s/%s/', rtrim($this->uri->getPath(), '/'), trim($containerName));
        $query = Query::build([...Query::parse($this->uri->getQuery()), ...$params]);

        return $this->uri->withPath($path)->withQuery($query);
    }

    /**
     * @param array<string, string> $params
     */
    private function uriForBlob(string $containerName, string $blobName, array $params = []): UriInterface
    {
        $uri = $this->uriForContainer($containerName, $params);
        $path = sprintf('%s/%s', rtrim($uri->getPath(), '/'), trim($blobName));

        return $this->uri->withPath($path);
    }

    /**
     * @param array<string, array<string, scalar>|callable|scalar> $options
     */
    public function containerExists(string $containerName, array $options = []): bool
    {
        $uri = $this->uriForContainer($containerName, [
            'restype' => 'container',
        ]);

        try {
            $response = $this->head($uri, $options);
        } catch (RequestException $e) {
            $response = $e->getResponse();

            if (null !== $response && 404 === $response->getStatusCode()) {
                return false;
            }

            throw $e;
        }

        return 200 === $response->getStatusCode();
    }

    /**
     * @param array<string, string>                   $metadata
     * @param array<string, callable|scalar|scalar[]> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/create-container
     */
    public function createContainer(string $containerName, array $metadata = [], array $options = []): PromiseInterface
    {
        $uri = $this->uriForContainer($containerName, [
            'restype' => 'container',
        ]);

        foreach ($metadata as $name => $value) {
            $options['headers']['x-ms-meta-'.$name] = $value;
        }

        return $this->putAsync($uri, $options);
    }

    /**
     * @param array<string, callable|scalar|scalar[]> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/delete-container
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/specifying-conditional-headers-for-blob-service-operations
     */
    public function deleteContainer(string $containerName, ?string $leaseId = null, array $options = []): PromiseInterface
    {
        $uri = $this->uriForContainer($containerName, [
            'restype' => 'container',
        ]);

        if (null !== $leaseId) {
            $options['headers']['x-ms-lease-id'] = $leaseId;
        }

        return $this->deleteAsync($uri, $options);
    }

    /**
     * @param array<string, callable|scalar|scalar[]> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/list-containers2
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/specifying-conditional-headers-for-blob-service-operations
     */
    public function listContainers(string $prefix = '', ?string $marker = null, int $maxResults = 5000, bool $withMetadata = false, array $options = []): PromiseInterface
    {
        $query = [
            'comp' => 'list',
            'prefix' => $prefix,
            'maxresults' => $maxResults,
        ];

        if (null !== $marker) {
            $query['marker'] = $marker;
        }

        if ($withMetadata) {
            $query['include'] = 'metadata';
        }

        $uri = $this->uri->withQuery(Query::build($query));

        return $this->getAsync($uri, $options);
    }

    /**
     * @param array<string, callable|scalar|scalar[]> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/get-container-properties
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/specifying-conditional-headers-for-blob-service-operations
     */
    public function getContainerProperties(string $containerName, ?string $leaseId = null, array $options = []): PromiseInterface
    {
        $uri = $this->uriForContainer($containerName, [
            'restype' => 'container',
        ]);

        if (null !== $leaseId) {
            $options['headers']['x-ms-lease-id'] = $leaseId;
        }

        return $this->headAsync($uri, $options);
    }

    /**
     * @param array<string, callable|scalar|scalar[]> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/get-container-metadata
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/specifying-conditional-headers-for-blob-service-operations
     */
    public function getContainerMetadata(string $containerName, ?string $leaseId = null, array $options = []): PromiseInterface
    {
        $uri = $this->uriForContainer($containerName, [
            'restype' => 'container',
            'comp' => 'metadata',
        ]);

        if (null !== $leaseId) {
            $options['headers']['x-ms-lease-id'] = $leaseId;
        }

        return $this->headAsync($uri, $options);
    }

    /**
     * @param array<string, string>                   $metadata
     * @param array<string, callable|scalar|scalar[]> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/set-container-metadata
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/specifying-conditional-headers-for-blob-service-operations
     */
    public function setContainerMetadata(string $containerName, array $metadata, ?string $leaseId = null, array $options = []): PromiseInterface
    {
        $uri = $this->uriForContainer($containerName, [
            'restype' => 'container',
            'comp' => 'metadata',
        ]);

        if (null !== $leaseId) {
            $options['headers']['x-ms-lease-id'] = $leaseId;
        }

        foreach ($metadata as $name => $value) {
            $options['headers']['x-ms-meta-'.$name] = $value;
        }

        return $this->putAsync($uri, $options);
    }

    /**
     * @param array<string, callable|scalar|scalar[]> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/lease-container
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/specifying-conditional-headers-for-blob-service-operations
     */
    public function leaseContainer(string $containerName, string $leaseAction, ?string $leaseId = null, ?int $leaseDuration = null, array $options = []): PromiseInterface
    {
        $uri = $this->uriForContainer($containerName, [
            'restype' => 'container',
            'comp' => 'lease',
        ]);

        $options['headers']['x-ms-lease-action'] = $leaseAction;

        if (null !== $leaseId) {
            $options['headers']['x-ms-lease-id'] = $leaseId;
        }

        if (null !== $leaseDuration) {
            $options['headers']['x-ms-lease-duration'] = $leaseDuration;
        }

        return $this->putAsync($uri, $options);
    }

    /**
     * @param array<string, array<string, scalar>|callable|scalar> $options
     */
    public function blobExists(string $containerName, string $blobName, array $options = []): bool
    {
        $uri = $this->uriForBlob($containerName, $blobName);

        try {
            $response = $this->head($uri, $options);
        } catch (RequestException $e) {
            $response = $e->getResponse();

            if (null !== $response && 404 === $response->getStatusCode()) {
                return false;
            }

            throw $e;
        }

        return 200 === $response->getStatusCode();
    }

    /**
     * @param resource|StreamInterface|string         $content
     * @param array<string, string>                   $metadata
     * @param array<string, string>                   $tags
     * @param array<string, callable|scalar|scalar[]> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/put-blob
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/specifying-conditional-headers-for-blob-service-operations
     */
    public function putBlob(
        string $containerName,
        string $blobName,
        $content,
        string $contentType = 'application/octet-stream',
        array $metadata = [],
        array $tags = [],
        array $options = []
    ): PromiseInterface {
        $uri = $this->uriForBlob($containerName, $blobName);

        if (false === $content instanceof StreamInterface) {
            $content = Utils::streamFor($content);
        }

        $options['body'] = $content;
        $options['headers']['content-type'] = $contentType;
        $options['headers']['content-length'] = $content->getSize();
        $options['headers']['x-ms-blob-type'] = 'BlockBlob ';

        foreach ($metadata as $name => $value) {
            $options['headers']['x-ms-meta-'.$name] = $value;
        }

        if (0 < count($tags)) {
            $options['headers']['x-ms-tags'] = Query::build($tags);
        }

        return $this->putAsync($uri, $options);
    }

    /**
     * @param array<string, callable|scalar|scalar[]> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/delete-blob
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/specifying-conditional-headers-for-blob-service-operations
     */
    public function deleteBlob(string $containerName, string $blobName, ?string $versionId = null, ?string $snapshot = null, bool $includeSnapshots = true, array $options = []): PromiseInterface
    {
        $params = [];

        if (null !== $versionId) {
            $params['versionid'] = $versionId;
        }

        if (null !== $snapshot) {
            $params['snapshot'] = $snapshot;
        }
        $uri = $this->uriForBlob($containerName, $blobName, $params);

        if (null === $snapshot) {
            $options['headers']['x-ms-delete-snapshots'] = $includeSnapshots ? 'include' : 'only';
        }

        return $this->deleteAsync($uri, $options);
    }

    /**
     * @param string[]                                $include
     * @param array<string, callable|scalar|scalar[]> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/list-blobs
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/specifying-conditional-headers-for-blob-service-operations
     */
    public function listBlobs(string $containerName, string $prefix = '', string $delimiter = '', ?string $marker = null, int $maxResults = 5000, array $include = [], array $options = []): PromiseInterface
    {
        $params = [
            'restype' => 'container',
            'comp' => 'list',
            'maxresults' => $maxResults,
        ];

        if ('' !== $prefix) {
            $params['prefix'] = $prefix;
        }

        if ('' !== $delimiter) {
            $params['delimiter'] = $delimiter;
        }

        if (null !== $marker) {
            $params['marker'] = $marker;
        }

        if (0 < count($include)) {
            $params['include'] = implode(',', $include);
        }

        $uri = $this->uriForContainer($containerName, $params);

        return $this->getAsync($uri, $options);
    }

    /**
     * @param array<string, callable|scalar|scalar[]> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/get-blob
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/specifying-conditional-headers-for-blob-service-operations
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/specifying-the-range-header-for-blob-service-operations
     */
    public function getBlob(string $containerName, string $blobName, ?string $snapshot = null, ?string $versionId = null, array $options = []): PromiseInterface
    {
        $params = [];

        if (null !== $snapshot) {
            $params['snapshot'] = $snapshot;
        }

        if (null !== $versionId) {
            $params['versionid'] = $versionId;
        }

        $uri = $this->uriForBlob($containerName, $blobName, $params);

        return $this->getAsync($uri, $options);
    }

    /**
     * @param array<string, callable|scalar|scalar[]> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/get-blob-properties
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/specifying-conditional-headers-for-blob-service-operations
     */
    public function getBlobProperties(string $containerName, string $blobName, ?string $snapshot = null, ?string $versionId = null, array $options = []): PromiseInterface
    {
        $params = [];

        if (null !== $snapshot) {
            $params['snapshot'] = $snapshot;
        }

        if (null !== $versionId) {
            $params['versionid'] = $versionId;
        }

        $uri = $this->uriForBlob($containerName, $blobName, $params);

        return $this->headAsync($uri, $options);
    }

    /**
     * @param array<string, callable|scalar|scalar[]> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/set-blob-properties
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/specifying-conditional-headers-for-blob-service-operations
     */
    public function setBlobProperties(string $containerName, string $blobName, array $options = []): PromiseInterface
    {
        $params = ['comp' => 'properties'];
        $uri = $this->uriForBlob($containerName, $blobName, $params);

        return $this->putAsync($uri, $options);
    }

    /**
     * @param array<string, callable|scalar|scalar[]> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/get-blob-metadata
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/specifying-conditional-headers-for-blob-service-operations
     */
    public function getBlobMetadata(string $containerName, string $blobName, ?string $snapshot = null, ?string $versionId = null, array $options = []): PromiseInterface
    {
        $params = ['comp' => 'metadata'];

        if (null !== $snapshot) {
            $params['snapshot'] = $snapshot;
        }

        if (null !== $versionId) {
            $params['versionid'] = $versionId;
        }

        $uri = $this->uriForBlob($containerName, $blobName, $params);

        return $this->headAsync($uri, $options);
    }

    /**
     * @param array<string, string>                   $metadata
     * @param array<string, callable|scalar|scalar[]> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/set-blob-metadata
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/specifying-conditional-headers-for-blob-service-operations
     */
    public function setBlobMetadata(string $containerName, string $blobName, array $metadata, array $options = []): PromiseInterface
    {
        $params = ['comp' => 'metadata'];
        $uri = $this->uriForBlob($containerName, $blobName, $params);

        foreach ($metadata as $key => $value) {
            $options['headers']['x-ms-meta-'.$key] = $value;
        }

        return $this->putAsync($uri, $options);
    }

    /**
     * @param array<string, callable|scalar|scalar[]> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/get-blob-tags
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/specifying-conditional-headers-for-blob-service-operations
     */
    public function getBlobTags(string $containerName, string $blobName, ?string $snapshot = null, ?string $versionId = null, array $options = []): PromiseInterface
    {
        $params = ['comp' => 'tags'];

        if (null !== $snapshot) {
            $params['snapshot'] = $snapshot;
        }

        if (null !== $versionId) {
            $params['versionid'] = $versionId;
        }

        $uri = $this->uriForBlob($containerName, $blobName, $params);

        return $this->getAsync($uri, $options);
    }

    /**
     * @param array<string, string>                   $tags
     * @param array<string, callable|scalar|scalar[]> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/set-blob-tags
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/specifying-conditional-headers-for-blob-service-operations
     */
    public function setBlobTags(string $containerName, string $blobName, array $tags, ?string $snapshot = null, ?string $versionId = null, array $options = []): PromiseInterface
    {
        $params = ['comp' => 'tags'];

        if (null !== $snapshot) {
            $params['snapshot'] = $snapshot;
        }

        if (null !== $versionId) {
            $params['versionid'] = $versionId;
        }

        $uri = $this->uriForBlob($containerName, $blobName, $params);

        $xml = new \SimpleXMLElement('<Tags></Tags>');
        $set = $xml->addChild('TagSet');

        foreach ($tags as $key => $value) {
            $tag = $set->addChild('Tag');
            $tag->addChild('Key', $key);
            $tag->addChild('Value', $value);
        }

        $body = $xml->asXML();

        $options['headers']['content-type'] = 'application/xml; charset=UTF-8';
        $options['headers']['content-length'] = strlen($body);
        $options['headers']['content-md5'] = md5($body);
        $options['body'] = $body;

        return $this->putAsync($uri, $options);
    }

    /**
     * @param array<string, callable|scalar|scalar[]> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/find-blobs-by-tags
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/specifying-conditional-headers-for-blob-service-operations
     */
    public function findBlobsByTags(string $expression, ?string $marker = null, int $maxResults = 5000, array $options = []): PromiseInterface
    {
        $query = [
            'comp' => 'blobs',
            'expression' => $expression,
            'maxresults' => $maxResults,
        ];

        if (null !== $marker) {
            $query['marker'] = $marker;
        }

        $uri = $this->uri->withQuery(Query::build($query));

        return $this->getAsync($uri, $options);
    }
}

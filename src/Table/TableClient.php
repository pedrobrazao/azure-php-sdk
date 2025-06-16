<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Table;

use AzurePhp\Storage\Common\Client\AbstractClient;
use AzurePhp\Storage\Table\Auth\SharedAccessKey;
use AzurePhp\Storage\Table\Exception\EntitySchemeException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Query;
use Psr\Http\Message\UriInterface;

final readonly class TableClient extends AbstractClient
{
    /**
     * @param array<callable|scalar|scalar[]> $options
     */
    public static function create(string $connectionString, array $options = []): self
    {
        $headers = [
            'dataserviceversion' => '3.0',
            'maxdataserviceversion' => '3.0;NetFx',
            'accept' => 'application/json',
            'accept-charset' => 'utf-8',
        ];

        return self::factory($connectionString, 'table', $headers, SharedAccessKey::class, $options);
    }

    /**
     * @param array<string, string> $params
     */
    private function uriForTable(string $tableName, array $params = []): UriInterface
    {
        $path = sprintf('%s/%s', rtrim($this->uri->getPath(), '/'), trim($tableName));
        $query = Query::build([...Query::parse($this->uri->getQuery()), ...$params]);

        return $this->uri->withPath($path)->withQuery($query);
    }

    /**
     * @param array<string, string> $params
     */
    private function uriForEntity(string $tableName, string $partitionKey, string $rowKey, array $params = []): UriInterface
    {
        $uri = $this->uriForTable($tableName, $params);
        $path = sprintf('%s(PartitionKey=\'%s\',RowKey=\'%s\')', $uri->getPath(), $partitionKey, $rowKey);

        return $uri->withPath($path);
    }

    /**
     * @param array<string, scalar>                                $data
     * @param array<string, array<string, scalar>|callable|scalar> $options
     *
     * @return array<string, array<string, scalar>|callable|scalar>
     */
    private function optionsForBody(array $data, array $options): array
    {
        $body = json_encode($data);

        $options['headers']['content-type'] = 'application/json';
        $options['headers']['content-length'] = strlen($body);
        $options['headers']['prefer'] ??= 'return-content';
        $options['body'] = $body;

        return $options;
    }

    /**
     * @param array<string, scalar> $data
     *
     * @return array<string, scalar>
     */
    private function enforceKeys(string $tableName, array $data): array
    {
        if (false === array_key_exists('PartitionKey', $data)) {
            $data['PartitionKey'] = $tableName;
        }

        if (false === array_key_exists('RowKey', $data)) {
            $data['RowKey'] = bin2hex(random_bytes(16));
        }

        return $data;
    }

    /**
     * @param array<string, array<string, scalar>|callable|scalar> $options
     */
    public function tableExists(string $tableName, array $options = []): bool
    {
        $uri = $this->uriForTable($tableName, [
            'comp' => 'acl',
        ]);

        try {
            $response = $this->get($uri, $options);
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
     * @param array<string, array<string, scalar>|callable|scalar> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/create-table
     */
    public function createTable(string $tableName, array $options = []): PromiseInterface
    {
        $uri = $this->uriForTable('Tables');
        $data = ['TableName' => $tableName];

        return $this->postAsync($uri, $this->optionsForBody($data, $options));
    }

    /**
     * @param array<string, array<string, scalar>|callable|scalar> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/delete-table
     */
    public function deleteTable(string $tableName, array $options = []): PromiseInterface
    {
        $uri = $this->uriForTable(sprintf('Tables(\'%s\')', $tableName));

        return $this->deleteAsync($uri, $options);
    }

    /**
     * @param array<string, array<string, scalar>|callable|scalar> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/query-tables
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/query-timeout-and-pagination
     */
    public function listTables(?string $nextTableName = null, array $options = []): PromiseInterface
    {
        $uri = $this->uriForTable('Tables');

        if (null !== $nextTableName) {
            $options['query']['NextTableName'] = $nextTableName;
        }

        return $this->getAsync($uri, $options);
    }

    /**
     * @param array<string, scalar>                                $data
     * @param array<string, array<string, scalar>|callable|scalar> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/insert-entity
     */
    public function insertEntity(string $tableName, array $data, array $options = []): PromiseInterface
    {
        $data = $this->enforceKeys($tableName, $data);
        $uri = $this->uriForTable($tableName);

        return $this->postAsync($uri, $this->optionsForBody($data, $options));
    }

    /**
     * @param array<string, scalar>                                $data
     * @param array<string, array<string, scalar>|callable|scalar> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/insert-or-merge-entity
     */
    public function insertOrMergeEntity(string $tableName, array $data, array $options = []): PromiseInterface
    {
        $data = $this->enforceKeys($tableName, $data);
        $uri = $this->uriForEntity($tableName, $data['PartitionKey'], $data['RowKey']);

        return $this->requestAsync('MERGE', $uri, $this->optionsForBody($data, $options));
    }

    /**
     * @param array<string, scalar>                                $data
     * @param array<string, array<string, scalar>|callable|scalar> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/insert-or-replace-entity#main
     */
    public function insertOrReplaceEntity(string $tableName, array $data, array $options = []): PromiseInterface
    {
        $data = $this->enforceKeys($tableName, $data);
        $uri = $this->uriForEntity($tableName, $data['PartitionKey'], $data['RowKey']);

        return $this->putAsync($uri, $this->optionsForBody($data, $options));
    }

    /**
     * @param array<string, scalar>                                $data
     * @param array<string, array<string, scalar>|callable|scalar> $options
     *
     * @throws EntitySchemeException
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/update-entity2
     */
    public function updateEntity(string $tableName, array $data, string $etag = '*', array $options = []): PromiseInterface
    {
        if (false === isset($data['PartitionKey'])) {
            throw new EntitySchemeException('Missing entity property "PartitionKey" while attempting to update it.');
        }

        if (false === isset($data['RowKey'])) {
            throw new EntitySchemeException('Missing entity property "RowKey" while attempting to update it.');
        }

        $uri = $this->uriForEntity($tableName, $data['PartitionKey'], $data['RowKey']);
        $options['headers']['If-Match'] = $etag;

        return $this->putAsync($uri, $this->optionsForBody($data, $options));
    }

    /**
     * @param array<string, scalar>                                $data
     * @param array<string, array<string, scalar>|callable|scalar> $options
     *
     * @throws EntitySchemeException
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/merge-entity
     */
    public function mergeEntity(string $tableName, array $data, string $etag = '*', array $options = []): PromiseInterface
    {
        if (false === isset($data['PartitionKey'])) {
            throw new EntitySchemeException('Missing entity property "PartitionKey" while attempting to merge it.');
        }

        if (false === isset($data['RowKey'])) {
            throw new EntitySchemeException('Missing entity property "RowKey" while attempting to merge it.');
        }


        $uri = $this->uriForEntity($tableName, $data['PartitionKey'], $data['RowKey']);
        $options['headers']['If-Match'] = $etag;

        return $this->requestAsync('MERGE', $uri, $this->optionsForBody($data, $options));
    }

    /**
     * @param array<string, array<string, scalar>|callable|scalar> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/delete-entity1
     */
    public function deleteEntity(string $tableName, string $partitionKey, string $rowKey, string $etag = '*', array $options = []): PromiseInterface
    {
        $uri = $this->uriForEntity($tableName, $partitionKey, $rowKey);
        $options['headers']['If-Match'] = $etag;

        return $this->deleteAsync($uri, $options);
    }

    /**
     * @param array<string, array<string, scalar>|callable|scalar> $options
     */
    public function entityExists(string $tableName, string $partitionKey, string $rowKey, array $options = []): bool
    {
        try {
            $response = $this->findEntity($tableName, $partitionKey, $rowKey, ['timestamp'])->wait();
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
     * @param string[]                                             $select
     * @param array<string, array<string, scalar>|callable|scalar> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/query-entities
     */
    public function findEntity(string $tableName, string $partitionKey, string $rowKey, array $select = [], array $options = []): PromiseInterface
    {
        $params = 0 < count($select) ? ['$select' => implode(',', $select)] : [];
        $uri = $this->uriForEntity($tableName, $partitionKey, $rowKey, $params);

        return $this->getAsync($uri, $options);
    }

    /**
     * @param string[]                                             $select
     * @param array<string, array<string, scalar>|callable|scalar> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/query-entities
     */
    public function listEntities(string $tableName, ?string $filter = null, ?int $top = null, array $select = [], ?string $NextPartitionKey = null, ?string $NextRowKey = null, array $options = []): PromiseInterface
    {
        $params = 0 < count($select) ? ['$select' => implode(',', $select)] : [];

        if (null !== $filter) {
            $params['$filter'] = urlencode($filter);
        }

        if (null !== $top) {
            $params['$top'] = $top;
        }

        if (null !== $NextPartitionKey) {
            $params['NextPartitionKey'] = $NextPartitionKey;
        }

        if (null !== $NextRowKey) {
            $params['NextRowKey'] = $NextRowKey;
        }

        $uri = $this->uriForTable($tableName, $params);

        return $this->getAsync($uri, $options);
    }
}

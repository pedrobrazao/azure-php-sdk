<?php

declare(strict_types=1);

namespace AzurePhp\Tests\Storage\Integration\Table;

use AzurePhp\Storage\Table\TableClient;
use AzurePhp\Tests\Storage\Integration\IntegrationTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class TableClientTest extends TestCase
{
    use IntegrationTrait;

    public function testCreateDeleteTable(): void
    {
        $client = TableClient::create($this->getConnectionString());
        $tableName = uniqid('Test');
        $this->assertFalse($client->tableExists($tableName));
        $client->createTable($tableName)->wait();
        $this->assertTrue($client->tableExists($tableName));
        $client->deleteTable($tableName)->wait();
        $this->assertFalse($client->tableExists($tableName));
    }

    public function testListTables(): void
    {
        $client = TableClient::create($this->getConnectionString());
        $names = [];
        for ($i = 0; $i < 3; ++$i) {
            $names[$i] = uniqid('Test');
            $client->createTable($names[$i])->wait();
            $this->assertTrue($client->tableExists($names[$i]));
        }
        $response = $client->listTables()->wait();
        $data = json_decode((string) $response->getBody()->getContents(), true);
        $this->assertArrayHasKey('value', $data);
        $this->assertIsArray($data['value']);
        $tables = [];
        foreach ($data['value'] as $table) {
            $this->assertIsArray($table);
            $this->assertArrayHasKey('TableName', $table);
            $tables[$table['TableName']] = $table;
        }
        foreach ($names as $name) {
            $this->assertArrayHasKey($name, $tables);
        }
    }

    public function testInsertDeleteEntity(): void
    {
        $client = TableClient::create($this->getConnectionString());
        $tableName = uniqid('Test');
        $data = [
            'name' => 'Joe Tester',
            'age' => 57,
            'score' => 4.98,
            'active' => true,
        ];
        $client->createTable($tableName)->wait();
        $response = $client->insertEntity($tableName, $data)->wait();
        $entity = json_decode((string) $response->getBody()->getContents(), true);
        foreach ($data as $key => $value) {
            $this->assertArrayHasKey($key, $entity);
            $this->assertSame($value, $entity[$key]);
        }
        $this->assertArrayHasKey('PartitionKey', $entity);
        $partitionKey = $entity['PartitionKey'];
        $this->assertArrayHasKey('RowKey', $entity);
        $rowKey = $entity['RowKey'];
        $this->assertArrayHasKey('odata.etag', $entity);
        $etag = $entity['odata.etag'];
        $this->assertTrue($client->entityExists($tableName, $partitionKey, $rowKey));
        $response = $client->deleteEntity($tableName, $partitionKey, $rowKey, $etag)->wait();
        $this->assertSame(204, $response->getStatusCode());
        $this->assertFalse($client->entityExists($tableName, $partitionKey, $rowKey));
        $client->deleteTable($tableName)->wait();
        $this->assertFalse($client->tableExists($tableName));
    }

    public function testInsertOrMergeEntity(): void
    {
        $client = TableClient::create($this->getConnectionString());
        $tableName = uniqid('Test');
        $partitionKey = $tableName;
        $rowKey = bin2hex(random_bytes(16));
        $data = [
            'PartitionKey' => $partitionKey,
            'RowKey' => $rowKey,
            'name' => 'Joe Tester',
            'age' => 57,
            'score' => 4.98,
            'active' => true,
        ];
        $client->createTable($tableName)->wait();
        $client->insertOrMergeEntity($tableName, $data)->wait();
        $this->assertTrue($client->entityExists($tableName, $partitionKey, $rowKey));
        $data['active'] = false;
        $data['extra'] = 'Added extra property';
        $client->insertOrMergeEntity($tableName, $data)->wait();
        $response = $client->findEntity($tableName, $partitionKey, $rowKey)->wait();
        $entity = json_decode((string) $response->getBody()->getContents(), true);
        foreach ($data as $key => $value) {
            $this->assertArrayHasKey($key, $entity);
            $this->assertSame($value, $entity[$key]);
        }
        $client->deleteTable($tableName)->wait();
        $this->assertFalse($client->tableExists($tableName));
    }

    public function testInsertOrReplaceEntity(): void
    {
        $client = TableClient::create($this->getConnectionString());
        $tableName = uniqid('Test');
        $partitionKey = $tableName;
        $rowKey = bin2hex(random_bytes(16));
        $data = [
            'PartitionKey' => $partitionKey,
            'RowKey' => $rowKey,
            'name' => 'Joe Tester',
            'age' => 57,
            'score' => 4.98,
            'active' => true,
        ];
        $client->createTable($tableName)->wait();
        $client->insertOrReplaceEntity($tableName, $data)->wait();
        $this->assertTrue($client->entityExists($tableName, $partitionKey, $rowKey));
        $data2 = [
            'PartitionKey' => $partitionKey,
            'RowKey' => $rowKey,
            'email' => 'joe@example.org',
            'phone' => '+441234567890',
        ];
        $client->insertOrReplaceEntity($tableName, $data2)->wait();
        $response = $client->findEntity($tableName, $partitionKey, $rowKey)->wait();
        $entity = json_decode((string) $response->getBody()->getContents(), true);
        unset($data['PartitionKey'], $data['RowKey']);

        foreach ($data as $key => $value) {
            $this->assertArrayNotHasKey($key, $entity);
        }
        foreach ($data2 as $key => $value) {
            $this->assertArrayHasKey($key, $entity);
            $this->assertSame($value, $entity[$key]);
        }
        $client->deleteTable($tableName)->wait();
        $this->assertFalse($client->tableExists($tableName));
    }

    public function testMergeEntity(): void
    {
        $client = TableClient::create($this->getConnectionString());
        $tableName = uniqid('Test');
        $partitionKey = $tableName;
        $rowKey = bin2hex(random_bytes(16));
        $data = [
            'PartitionKey' => $partitionKey,
            'RowKey' => $rowKey,
            'name' => 'Joe Tester',
            'age' => 57,
            'score' => 4.98,
            'active' => true,
        ];
        $client->createTable($tableName)->wait();
        $client->insertOrMergeEntity($tableName, $data)->wait();
        $this->assertTrue($client->entityExists($tableName, $partitionKey, $rowKey));
        $data['active'] = false;
        $data['extra'] = 'Added extra property';
        $client->mergeEntity($tableName, $data)->wait();
        $response = $client->findEntity($tableName, $partitionKey, $rowKey)->wait();
        $entity = json_decode((string) $response->getBody()->getContents(), true);
        foreach ($data as $key => $value) {
            $this->assertArrayHasKey($key, $entity);
            $this->assertSame($value, $entity[$key]);
        }
        $client->deleteTable($tableName)->wait();
        $this->assertFalse($client->tableExists($tableName));
    }

    public function testInsertUpdateEntity(): void
    {
        $client = TableClient::create($this->getConnectionString());
        $tableName = uniqid('Test');
        $partitionKey = $tableName;
        $rowKey = bin2hex(random_bytes(16));
        $data = [
            'PartitionKey' => $partitionKey,
            'RowKey' => $rowKey,
            'name' => 'Joe Tester',
            'age' => 57,
            'score' => 4.98,
            'active' => true,
        ];
        $client->createTable($tableName)->wait();
        $client->insertOrReplaceEntity($tableName, $data)->wait();
        $this->assertTrue($client->entityExists($tableName, $partitionKey, $rowKey));
        $data2 = [
            'PartitionKey' => $partitionKey,
            'RowKey' => $rowKey,
            'email' => 'joe@example.org',
            'phone' => '+441234567890',
        ];
        $client->updateEntity($tableName, $data2)->wait();
        $response = $client->findEntity($tableName, $partitionKey, $rowKey)->wait();
        $entity = json_decode((string) $response->getBody()->getContents(), true);
        unset($data['PartitionKey'], $data['RowKey']);

        foreach ($data as $key => $value) {
            $this->assertArrayNotHasKey($key, $entity);
        }
        foreach ($data2 as $key => $value) {
            $this->assertArrayHasKey($key, $entity);
            $this->assertSame($value, $entity[$key]);
        }
        $client->deleteTable($tableName)->wait();
        $this->assertFalse($client->tableExists($tableName));
    }

    public function testListEntities(): void
    {
        $client = TableClient::create($this->getConnectionString());
        $tableName = uniqid('Test');
        $data = [
            'GB' => ['name' => 'United Kingdom', 'continent' => 'Europe'],
            'PT' => ['name' => 'Portugal', 'continent' => 'Europe'],
            'FR' => ['name' => 'France', 'continent' => 'Europe'],
            'ES' => ['name' => 'Spain', 'continent' => 'Europe'],
            'IT' => ['name' => 'Italy', 'continent' => 'Europe'],
            'DE' => ['name' => 'Germany', 'continent' => 'Europe'],
            'US' => ['name' => 'United States', 'continent' => 'North America'],
            'CA' => ['name' => 'Canada', 'continent' => 'North America'],
            'MX' => ['name' => 'Mexico', 'continent' => 'North America'],
            'BR' => ['name' => 'Brazil', 'continent' => 'South America'],
            'AR' => ['name' => 'Argentina', 'continent' => 'South America'],
        ];
        $client->createTable($tableName)->wait();
        foreach ($data as $code => $row) {
            $row['code'] = $code;
            $client->insertEntity($tableName, $row)->wait();
        }
        $response = $client->listEntities($tableName)->wait();
        $result = json_decode((string) $response->getBody()->getContents(), true);
        $this->assertIsArray($result['value']);
        $this->assertCount(count($data), $result['value']);
        foreach ($result['value'] as $entity) {
            $this->assertArrayHasKey('code', $entity);
            $this->assertArrayHasKey($entity['code'], $data);
            unset($data[$entity['code']]);
        }
        $this->assertEmpty($data);
        $client->deleteTable($tableName)->wait();
        $this->assertFalse($client->tableExists($tableName));
    }
}

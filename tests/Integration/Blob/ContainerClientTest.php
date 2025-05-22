<?php

declare(strict_types=1);

namespace AzurePhp\Tests\Storage\Integration\Blob;

use AzurePhp\Storage\Blob\AccountClient;
use AzurePhp\Storage\Blob\Model\BlobUpload;
use AzurePhp\Storage\Blob\Model\Metadata;
use AzurePhp\Tests\Storage\Integration\AbstractIntegrationTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ContainerClientTest extends AbstractIntegrationTestCase
{
    public function testContainerContainer(): void
    {
        $containerName = uniqid('test-');

        $client = AccountClient::fromConnectionString($this->getLocalConnectionString())->getContainerClient($containerName);
        $this->assertFalse($client->exists());

        $client->create();
        $this->assertTrue($client->exists());

        $client->delete();
        $this->assertFalse($client->exists());
    }

    public function testListBlobs(): void
    {
        $client = AccountClient::fromConnectionString($this->getLocalConnectionString())->getContainerClient(uniqid('test-'));
        $client->create();
        $this->assertTrue($client->exists());

        $names = [];

        for ($i = 0; $i < random_int(5, 20); ++$i) {
            $name = uniqid().'.txt';
            $client->getBlobClient($name)->upload(BlobUpload::fromString(random_bytes(random_int(100, 300))));
            $names[] = $name;
        }

        $blobs = [];

        foreach ($client->listBlobs() as $blob) {
            $blobs[$blob->name] = $blob;
        }

        foreach ($names as $name) {
            $this->assertArrayHasKey($name, $blobs);
        }

        $client->delete();
        $this->assertFalse($client->exists());
    }

    public function testSetMetadata(): void
    {
        $client = AccountClient::fromConnectionString($this->getLocalConnectionString())->getContainerClient(uniqid('test-'));
        $client->create();
        $this->assertTrue($client->exists());


        $metadata = [
            'foo' => 'bar',
            'baz' => 'zaf',
        ];

        $client->setMetadata(new Metadata($metadata));

        $properties = $client->getProperties();

        foreach ($metadata as $key => $value) {
            $this->assertArrayHasKey($key, $properties->metadata->toArray());
            $this->assertSame($value, $properties->metadata->toArray()[$key]);
        }

        $client->delete();
        $this->assertFalse($client->exists());
    }
}

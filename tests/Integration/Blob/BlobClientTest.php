<?php

declare(strict_types=1);

namespace AzurePhp\Tests\Storage\Integration\Blob;

use AzurePhp\Storage\Blob\BlobClient;
use AzurePhp\Tests\Storage\Integration\IntegrationTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class BlobClientTest extends TestCase
{
    use IntegrationTrait;

    public function testCreateDeleteContainer(): void
    {
        $client = BlobClient::create($this->getConnectionString());
        $containerName = uniqid('test-');
        $this->assertFalse($client->containerExists($containerName));
        $client->createContainer($containerName)->wait();
        $this->assertTrue($client->containerExists($containerName));
        $client->deleteContainer($containerName)->wait();
        $this->assertFalse($client->containerExists($containerName));
    }

    public function testListContainers(): void
    {
        $client = BlobClient::create($this->getConnectionString());
        $names = [];
        for ($i = 0; $i < 5; ++$i) {
            $name = uniqid('test-');
            $this->assertFalse($client->containerExists($name));
            $client->createContainer($name)->wait();
            $this->assertTrue($client->containerExists($name));
            $names[] = $name;
        }
        $response = $client->listContainers()->wait();
        $xml = new \SimpleXMLElement($response->getBody()->getContents());
        $this->assertObjectHasProperty('Containers', $xml);
        $containers = [];
        foreach ($xml->Containers->children() as $container) {
            $this->assertObjectHasProperty('Name', $container);
            $containers[(string) $container->Name] = $container;
        }
        foreach ($names as $name) {
            $this->assertArrayHasKey($name, $containers);
            unset($containers[$name]);
            $client->deleteContainer($name)->wait();
            $this->assertFalse($client->containerExists($name));
        }
    }

    public function testContainerProperties(): void
    {
        $client = BlobClient::create($this->getConnectionString());
        $containerName = uniqid('test-');
        $this->assertFalse($client->containerExists($containerName));
        $client->createContainer($containerName)->wait();
        $this->assertTrue($client->containerExists($containerName));
        $response = $client->getContainerProperties($containerName)->wait();
        $this->assertTrue($response->hasHeader('Last-Modified'));
        $client->deleteContainer($containerName)->wait();
        $this->assertFalse($client->containerExists($containerName));
    }

    public function testContainerMetadata(): void
    {
        $client = BlobClient::create($this->getConnectionString());
        $containerName = uniqid('test-');
        $this->assertFalse($client->containerExists($containerName));
        $client->createContainer($containerName)->wait();
        $this->assertTrue($client->containerExists($containerName));
        $metadata = [];
        for ($i = 0; $i < 4; ++$i) {
            $key = $i + 1;
            $metadata['key'.$key] = 'value'.$key;
        }
        $client->setContainerMetadata($containerName, $metadata)->wait();
        $response = $client->getContainerProperties($containerName)->wait();
        foreach ($metadata as $key => $value) {
            $name = 'x-ms-meta-'.$key;
            $this->assertTrue($response->hasHeader($name));
            $this->assertSame($value, $response->getHeader($name)[0]);
        }
        $client->deleteContainer($containerName)->wait();
        $this->assertFalse($client->containerExists($containerName));
    }

    public function testLeaseContainer(): void
    {
        $client = BlobClient::create($this->getConnectionString());
        $containerName = uniqid('test-');
        $this->assertFalse($client->containerExists($containerName));
        $client->createContainer($containerName)->wait();
        $this->assertTrue($client->containerExists($containerName));
        $response = $client->leaseContainer($containerName, 'acquire', null, -1)->wait();
        $this->assertTrue($response->hasHeader('x-ms-lease-id'));
        $leaseId = $response->getHeader('x-ms-lease-id')[0];
        $client->deleteContainer($containerName, $leaseId)->wait();
        $this->assertFalse($client->containerExists($containerName));
    }

    public function testPutDeleteBlob(): void
    {
        $client = BlobClient::create($this->getConnectionString());
        $containerName = uniqid('test-');
        $client->createContainer($containerName)->wait();
        $blobName = uniqid('test-').'.txt';
        $contentType = 'text/plain';
        $content = random_bytes(random_int(100, 300));
        $this->assertFalse($client->blobExists($containerName, $blobName));
        $client->putBlob($containerName, $blobName, $content, $contentType)->wait();
        $this->assertTrue($client->blobExists($containerName, $blobName));
        $client->deleteBlob($containerName, $blobName)->wait();
        $this->assertFalse($client->blobExists($containerName, $blobName));
        $client->deleteContainer($containerName)->wait();
    }

    public function testListBlobs(): void
    {
        $client = BlobClient::create($this->getConnectionString());
        $containerName = uniqid('test-');
        $client->createContainer($containerName)->wait();
        $names = [];
        for ($i = 0; $i < 5; ++$i) {
            $blobName = uniqid('test-').'.txt';
            $contentType = 'text/plain';
            $content = random_bytes(random_int(100, 300));
            $this->assertFalse($client->blobExists($containerName, $blobName));
            $client->putBlob($containerName, $blobName, $content, $contentType)->wait();
            $this->assertTrue($client->blobExists($containerName, $blobName));
            $names[] = $blobName;
        }
        $response = $client->listBlobs($containerName)->wait();
        $xml = new \SimpleXMLElement($response->getBody()->getContents());
        $this->assertObjectHasProperty('ContainerName', $xml->attributes());
        $this->assertSame($containerName, (string) $xml->attributes()->ContainerName);
        $blobs = [];
        foreach ($xml->Blobs->children() as $blob) {
            $blobs[(string) $blob->Name] = $blob;
        }
        foreach ($names as $name) {
            $this->assertArrayHasKey($name, $blobs);
        }
        $client->deleteContainer($containerName)->wait();
    }

    public function testGetBlob(): void
    {
        $client = BlobClient::create($this->getConnectionString());
        $containerName = uniqid('test-');
        $client->createContainer($containerName)->wait();
        $blobName = uniqid('test-').'.txt';
        $contentType = 'text/plain';
        $content = random_bytes(random_int(100, 300));
        $this->assertFalse($client->blobExists($containerName, $blobName));
        $client->putBlob($containerName, $blobName, $content, $contentType)->wait();
        $this->assertTrue($client->blobExists($containerName, $blobName));
        $response = $client->getBlob($containerName, $blobName)->wait();
        $this->assertSame($content, $response->getBody()->getContents());
        $client->deleteContainer($containerName)->wait();
    }

    public function testBlobProperties(): void
    {
        $client = BlobClient::create($this->getConnectionString());
        $containerName = uniqid('test-');
        $client->createContainer($containerName)->wait();
        $blobName = uniqid('test-').'.txt';
        $content = random_bytes(random_int(100, 300));
        $this->assertFalse($client->blobExists($containerName, $blobName));
        $client->putBlob($containerName, $blobName, $content)->wait();
        $this->assertTrue($client->blobExists($containerName, $blobName));
        $response = $client->getBlobProperties($containerName, $blobName)->wait();
        $this->assertSame('application/octet-stream', $response->getHeader('content-type')[0]);
        $client->deleteContainer($containerName)->wait();
    }
}

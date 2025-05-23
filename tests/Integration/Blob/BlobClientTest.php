<?php

declare(strict_types=1);

namespace AzurePhp\Tests\Storage\Integration\Blob;

use AzurePhp\Storage\Blob\AccountClient;
use AzurePhp\Storage\Blob\Model\BlobUpload;
use AzurePhp\Storage\Common\Model\Metadata;
use AzurePhp\Storage\Blob\Model\Tags;
use AzurePhp\Tests\Storage\Integration\AbstractIntegrationTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class BlobClientTest extends AbstractIntegrationTestCase
{
    public function testUploadBlob(): void
    {
        $containerName = uniqid('test-');
        $blobName = uniqid().'.txt';
        $blobContents = random_bytes(random_int(100, 300));
        $contentLength = strlen($blobContents);
        $contentType = 'text/plain';

        $containerClient = AccountClient::fromConnectionString($this->getLocalConnectionString())->getContainerClient($containerName);
        $containerClient->create();
        $this->assertTrue($containerClient->exists());

        $blobClient = $containerClient->getBlobClient($blobName);
        $this->assertFalse($blobClient->exists());

        $blobClient->upload(BlobUpload::fromString($blobContents, $contentType));
        $this->assertTrue($blobClient->exists());

        $properties = $blobClient->getProperties();
        $this->assertSame($contentType, $properties->contentType);
        $this->assertSame($contentLength, $properties->contentLength);

        $blob = $blobClient->download();
        $this->assertSame($blobContents, (string) $blob);

        $blobClient->delete();
        $this->assertFalse($blobClient->exists());

        $containerClient->delete();
        $this->assertFalse($containerClient->exists());
    }

    public function testSetMetadata(): void
    {
        $containerName = uniqid('test-');
        $containerClient = AccountClient::fromConnectionString($this->getLocalConnectionString())->getContainerClient($containerName);
        $containerClient->create();
        $this->assertTrue($containerClient->exists());

        $blobName = uniqid().'.txt';
        $blobClient = $containerClient->getBlobClient($blobName);
        $blobClient->upload(BlobUpload::fromString(random_bytes(random_int(100, 300))));
        $this->assertTrue($blobClient->exists());
        $metadata = [
            'foo' => 'bar',
            'baz' => 'zaf',
        ];

        $blobClient->setMetadata(new Metadata($metadata));

        $properties = $blobClient->getProperties();

        foreach ($metadata as $key => $value) {
            $this->assertArrayHasKey($key, $properties->metadata->toArray());
            $this->assertSame($value, $properties->metadata->toArray()[$key]);
        }

        $containerClient->delete();
        $this->assertFalse($containerClient->exists());
    }

    public function testSeTags(): void
    {
        $containerName = uniqid('test-');
        $containerClient = AccountClient::fromConnectionString($this->getLocalConnectionString())->getContainerClient($containerName);
        $containerClient->create();
        $this->assertTrue($containerClient->exists());

        $blobName = uniqid().'.txt';
        $blobClient = $containerClient->getBlobClient($blobName);
        $blobClient->upload(BlobUpload::fromString(random_bytes(random_int(100, 300))));
        $this->assertTrue($blobClient->exists());
        $tags = [
            'foo' => 'bar',
            'baz' => 'zaf',
        ];

        $blobClient->setTags(new Tags($tags));

        $blobTags = $blobClient->getTags();

        foreach ($tags as $key => $value) {
            $this->assertArrayHasKey($key, $blobTags->toArray());
            $this->assertSame($value, $blobTags->toArray()[$key]);
        }

        $containerClient->delete();
        $this->assertFalse($containerClient->exists());
    }
}

<?php

declare(strict_types=1);

namespace AzurePhp\Tests\Storage\Integration\Queue;

use AzurePhp\Storage\Common\Model\Metadata;
use AzurePhp\Storage\Queue\AccountClient;
use AzurePhp\Tests\Storage\Integration\AbstractIntegrationTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class QueueClientTest extends AbstractIntegrationTestCase
{
    public function testCreateQueue(): void
    {
        $client = AccountClient::fromConnectionString($this->getLocalConnectionString())->getQueueClient(uniqid('test-'));
        $this->assertFalse($client->exists());

        $client->create();
        $this->assertTrue($client->exists());

        $client->delete();
        $this->assertFalse($client->exists());
    }

    public function testSetMetadata(): void
    {
        $metadata = [
            'foo' => 'bar',
            'baz' => 'zaf',
        ];

        $client = AccountClient::fromConnectionString($this->getLocalConnectionString())->getQueueClient(uniqid('test-'));
        $this->assertFalse($client->exists());

        $client->create();
        $this->assertTrue($client->exists());

        $client->setMetadata(new Metadata($metadata));
        $queueMetadata = $client->getMetadata();


        foreach ($metadata as $key => $value) {
            $this->assertArrayHasKey($key, $queueMetadata->toArray());
            $this->assertSame($value, $queueMetadata->toArray()[$key]);
        }

        $client->delete();
        $this->assertFalse($client->exists());
    }
}

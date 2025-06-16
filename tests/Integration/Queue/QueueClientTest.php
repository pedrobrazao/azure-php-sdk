<?php

declare(strict_types=1);

namespace AzurePhp\Tests\Storage\Integration\Queue;

use AzurePhp\Storage\Queue\QueueClient;
use AzurePhp\Tests\Storage\Integration\IntegrationTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class QueueClientTest extends TestCase
{
    use IntegrationTrait;

    public function testCreateDeleteQueue(): void
    {
        $client = QueueClient::create($this->getConnectionString());
        $queueName = uniqid('test-');
        $this->assertFalse($client->queueExists($queueName));
        $client->createQueue($queueName)->wait();
        $this->assertTrue($client->queueExists($queueName));
        $client->deleteQueue($queueName)->wait();
        $this->assertFalse($client->queueExists($queueName));
    }

    public function testListQueues(): void
    {
        $client = QueueClient::create($this->getConnectionString());
        $names = [];
        for ($i = 0; $i < 5; ++$i);
        $name = uniqid('test-');
        $names[] = $name;
        $client->createQueue($name)->wait();
        $this->assertTrue($client->queueExists($name));

        $response = $client->listQueues()->wait();
        $xml = new \SimpleXMLElement($response->getBody()->getContents());
        $this->assertObjectHasProperty('Queues', $xml);
        $queues = [];
        foreach ($xml->Queues->children() as $queue) {
            $queues[(string) $queue->Name] = $queue;
        }
        foreach ($names as $name) {
            $this->assertArrayHasKey($name, $queues);
            $client->deleteQueue($name)->wait();
            $this->assertFalse($client->queueExists($name));
        }
    }
}

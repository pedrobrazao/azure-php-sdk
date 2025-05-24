<?php

declare(strict_types=1);

namespace AzurePhp\Tests\Storage\Integration\Queue;

use AzurePhp\Storage\Queue\AccountClient;
use AzurePhp\Storage\Queue\Model\Queue;
use AzurePhp\Tests\Storage\Integration\AbstractIntegrationTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class AccountClientTest extends AbstractIntegrationTestCase
{
    public function testListQueue(): void
    {
        $client = AccountClient::fromConnectionString($this->getLocalConnectionString());
        $names = [];

        for ($i = 0; $i < random_int(3, 10); ++$i) {
            $name = uniqid('test-');
            $names[] = $name;
            $client->getQueueClient($name)->create();
        }

        $queues = [];

        foreach ($client->listQueues('', true) as $queue) {
            $queues[$queue->name] = $queue;
        }

        foreach ($names as $name) {
            $this->assertArrayHasKey($name, $queues);
            $this->assertInstanceOf(Queue::class, $queues[$name]);
            $client->getQueueClient($name)->delete();
        }
    }
}

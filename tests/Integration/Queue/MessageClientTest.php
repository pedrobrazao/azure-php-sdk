<?php

declare(strict_types=1);

namespace AzurePhp\Tests\Storage\Integration\Queue;

use AzurePhp\Storage\Queue\AccountClient;
use AzurePhp\Storage\Queue\MessageClient;
use AzurePhp\Storage\Queue\QueueClient;
use AzurePhp\Tests\Storage\Integration\AbstractIntegrationTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;

/**
 * @internal
 *
 * @coversNothing
 */
final class MessageClientTest extends AbstractIntegrationTestCase
{
    private ?QueueClient $queueClient = null;

    public function createTestQueue(): QueueClient
    {
        
        $this->queueClient = AccountClient::fromConnectionString($this->getLocalConnectionString())->getQueueClient(uniqid('test-'));
        $this->assertFalse($this->queueClient->exists());

        $this->queueClient->create();
        $this->assertTrue($this->queueClient->exists());

        return $this->queueClient;
    }

    public function deleteTestQueue(): void
    {
        if (null === $this->queueClient) {
            return;
        }

        $this->assertTrue($this->queueClient->exists());

        $this->queueClient->delete();
        $this->assertFalse($this->queueClient->exists());
    }

    public function testPutMessage(): void
    {
        $messageClient = $this->createTestQueue()->getMessageClient();

        $text = json_encode(['subject' => 'My message subject']);
        $message = $messageClient->put($text);

        $this->assertSame($message->id, $messageClient->get()->first()->id);

        $this->deleteTestQueue();
    }

    public function testPeekMessage(): void
    {
        $messageClient = $this->createTestQueue()->getMessageClient();

        $text = json_encode(['subject' => 'My message subject']);
        $message = $messageClient->put($text);

        $this->assertSame($message->id, $messageClient->peek()->first()->id);

        $this->deleteTestQueue();
    }

    public function testUpdateMessage(): void
    {
        $client = $this->createStub(ClientInterface::class);
        $client->method('send')->willReturn(new Response(204));
        $uri = new Uri($_ENV['AZURE_STORAGE_QUEUE_ENDPOINT'].'/myqueue/messages');
        $messageClient = new MessageClient($client, $uri);

        // @phpstan-ignore-next-line
        $this->assertNull($messageClient->update('id', 'pop-receipt', 'new-message'));
    }

    public function testClearMessages(): void
    {
        $messageClient = $this->createTestQueue()->getMessageClient();

        for ($i = 0; $i < 3; ++$i) {
            $messageClient->put(uniqid());
            $this->assertCount(1, $messageClient->peek());
        }

        $messageClient->clear();
        $this->assertCount(0, $messageClient->peek());

        $this->deleteTestQueue();
    }
}

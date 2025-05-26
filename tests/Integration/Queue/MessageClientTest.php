<?php

declare(strict_types=1);

namespace AzurePhp\Tests\Storage\Integration\Queue;

use AzurePhp\Storage\Queue\AccountClient;
use AzurePhp\Storage\Queue\Model\Message;
use AzurePhp\Tests\Storage\Integration\AbstractIntegrationTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class MessageClientTest extends AbstractIntegrationTestCase
{
    public function testPutMessage(): void
    {
        $queueClient = AccountClient::fromConnectionString($this->getLocalConnectionString())->getQueueClient(uniqid('test-'));
        $this->assertFalse($queueClient->exists());

        $queueClient->create();
        $this->assertTrue($queueClient->exists());

        $text = json_encode(['subject' => 'My message subject']);

        $message = $queueClient->getMessageClient()->put($text);
        $this->assertInstanceOf(Message::class, $message);
        $this->assertNotSame('', $message->id);

        $queueClient->delete();
        $this->assertFalse($queueClient->exists());
    }

    public function testGetMessage(): void
    {
        $queueClient = AccountClient::fromConnectionString($this->getLocalConnectionString())->getQueueClient(uniqid('test-'));
        $this->assertFalse($queueClient->exists());

        $queueClient->create();
        $this->assertTrue($queueClient->exists());

        $messageClient = $queueClient->getMessageClient();
        $text = json_encode(['subject' => 'My message subject']);
        $message = $messageClient->put($text);
        $this->assertInstanceOf(Message::class, $message);
        $this->assertNotSame('', $message->id);

        $messageList = $messageClient->get();
        $this->assertCount(1, $messageList);
        $this->assertSame(1, $messageList->messages[0]->dequeueCount);
        $this->assertSame($text, $messageList->messages[0]->text);

        $this->assertEmpty($messageClient->get()->messages);

        $queueClient->delete();
        $this->assertFalse($queueClient->exists());
    }

    public function testPeekMessage(): void
    {
        $queueClient = AccountClient::fromConnectionString($this->getLocalConnectionString())->getQueueClient(uniqid('test-'));
        $this->assertFalse($queueClient->exists());

        $queueClient->create();
        $this->assertTrue($queueClient->exists());

        $messageClient = $queueClient->getMessageClient();
        $text = json_encode(['subject' => 'My message subject']);
        $message = $messageClient->put($text);
        $this->assertInstanceOf(Message::class, $message);
        $this->assertNotSame('', $message->id);

        $messageList = $messageClient->peek();
        $this->assertCount(1, $messageList);
        $this->assertSame(0, $messageList->messages[0]->dequeueCount);
        $this->assertSame($text, $messageList->messages[0]->text);

        $messageList2 = $messageClient->peek();
        $this->assertCount(1, $messageList2);
        $this->assertSame(0, $messageList2->messages[0]->dequeueCount);
        $this->assertSame($text, $messageList2->messages[0]->text);

        $queueClient->delete();
        $this->assertFalse($queueClient->exists());
    }

    public function testDeleteMessage(): void
    {
        $queueClient = AccountClient::fromConnectionString($this->getLocalConnectionString())->getQueueClient(uniqid('test-'));
        $this->assertFalse($queueClient->exists());

        $queueClient->create();
        $this->assertTrue($queueClient->exists());

        $messageClient = $queueClient->getMessageClient();
        $text = json_encode(['subject' => 'My message subject']);
        $message = $messageClient->put($text);
        $this->assertInstanceOf(Message::class, $message);
        $this->assertNotSame('', $message->id);

        $messageList = $messageClient->peek();
        $this->assertCount(1, $messageList);

        $messageClient->delete($message->id, $message->popReceipt);

        $messageList2 = $messageClient->peek();
        $this->assertCount(0, $messageList2);

        $queueClient->delete();
        $this->assertFalse($queueClient->exists());
    }
}

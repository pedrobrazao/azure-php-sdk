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
}

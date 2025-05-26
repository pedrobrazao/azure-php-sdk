<?php

declare(strict_types=1);

namespace AzurePhp\Tests\Storage\Unit\Queue\Model;

use AzurePhp\Storage\Queue\Model\Message;
use AzurePhp\Storage\Queue\Model\MessageList;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class MessageListTest extends TestCase
{
    #[DataProvider('xmlProvider')]
    public function testFromXml(\SimpleXMLElement $xml, int $numberOfMessages): void
    {
        $messageList = MessageList::fromXml($xml);
        $this->assertCount($numberOfMessages, $messageList);

        foreach ($messageList->messages as $message) {
            $this->assertInstanceOf(Message::class, $message);
            $this->assertNotSame('', $message->id);
            $this->assertNotNull($message->text);
        }
    }

    /**
     * @return array<array<int|\SimpleXMLElement>>
     */
    public static function xmlProvider(): array
    {
        return [
            [self::generateMessageListXml(0), 0],
            [self::generateMessageListXml(1), 1],
            [self::generateMessageListXml(2), 2],
            [self::generateMessageListXml(5), 5],
        ];
    }

    public static function generateMessageListXml(int $numberOfMessages = 1): \SimpleXMLElement
    {
        $xml = new \SimpleXMLElement('<QueueMessagesList></QueueMessagesList>');

        for ($i = 0; $i < $numberOfMessages; ++$i) {
            $message = $xml->addChild('QueueMessage');
            $message->addChild('MessageId', uniqid());
            $message->addChild('InsertionTime', date('c'));
            $message->addChild('ExpirationTime', date('c', time() + 86400));
            $message->addChild('PopReceipt', bin2hex(random_bytes(32)));
            $message->addChild('TimeNextVisible', date('c', time() + 30));
            $message->addChild('DequeueCount', '1');
            $message->addChild('MessageText', bin2hex(random_bytes(128)));
        }

        return $xml;
    }
}

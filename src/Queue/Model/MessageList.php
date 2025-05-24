<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Queue\Model;

use AzurePhp\Storage\Common\Exception\EmptyListException;

final class MessageList implements \Countable
{
    /**
     * @param Message[] $messages
     */
    public function __construct(
        private array $messages
    ) {}

    public function count(): int
    {
        return count($this->messages);
    }

    public static function fromXml(\SimpleXMLElement $xml): self
    {
        $messages = [];

        foreach ($xml->QueueMessagesList->children() as $message) {
            $messages[] = Message::fromXml($message);
        }

        return new self($messages);
    }

    public function shift(): Message
    {
        if (null === $message = array_shift($this->messages)) {
            throw new EmptyListException('There isn\'t any message in the list.');
        }

        return $message;
    }
}

<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Queue\Model;

use AzurePhp\Storage\Common\Exception\EmptyListException;

final readonly class MessageList implements \Countable
{
    /**
     * @param Message[] $messages
     */
    public function __construct(
        public array $messages
    ) {}

    public static function fromXml(\SimpleXMLElement $xml): self
    {
        $messages = [];

        foreach ($xml->children() as $message) {
            $messages[] = Message::fromXml($message);
        }

        return new self($messages);
    }

    public function count(): int
    {
        return count($this->messages);
    }

    public function first(): Message
    {
        if (0 === $this->count()) {
            throw new EmptyListException();
        }

        return $this->messages[0];
    }
}

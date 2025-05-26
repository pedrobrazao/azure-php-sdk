<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Queue\Model;

final readonly class MessageList implements \Countable
{
    /**
     * @param Message[] $messages
     */
    public function __construct(
        public array $messages
    ) {}

    public function count(): int
    {
        return count($this->messages);
    }

    public static function fromXml(\SimpleXMLElement $xml): self
    {
        $messages = [];

        foreach ($xml->children() as $message) {
            $messages[] = Message::fromXml($message);
        }

        return new self($messages);
    }
}

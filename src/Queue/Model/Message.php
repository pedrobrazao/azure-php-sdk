<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Queue\Model;

final readonly class Message
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $insertionTime,
        public \DateTimeImmutable $expirationTime,
        public \DateTimeImmutable $visibilityTime,
        public string $popReceipt,
        public int $dequeueCount = 0,
        public ?string $text = null
    ) {}

    public static function fromXml(\SimpleXMLElement $xml): self
    {
        $text = null;

        if (null !== $xml->MessageText) {
            $text = (string) $xml->MessageText;
        }

        return new self(
            (string) $xml->MessageId,
            new \DateTimeImmutable((string) $xml->InsertionTime),
            new \DateTimeImmutable((string) $xml->ExpirationTime),
            new \DateTimeImmutable((string) $xml->TimeNextVisible),
            (string) $xml->PopReceipt,
            (int) $xml->DequeueCount,
            $text
        );
    }
}

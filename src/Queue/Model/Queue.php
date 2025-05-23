<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Queue\Model;

use AzurePhp\Storage\Common\Model\Metadata;

final readonly class Queue
{
    public function __construct(
        public string $name,
        public Metadata $metadata
    ) {}

    public static function fromXml(\SimpleXMLElement $xml): self
    {
        return new self(
            (string) $xml->Name,
            Metadata::fromXml($xml->Properties)
        );
    }
}

<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Blob\Model;

final readonly class Container
{
    public function __construct(
        public string $name,
        public ContainerProperties $properties
    ) {}

    public static function fromXml(\SimpleXMLElement $xml): self
    {
        return new self(
            (string) $xml->Name,
            ContainerProperties::fromXml($xml->Properties)
        );
    }
}

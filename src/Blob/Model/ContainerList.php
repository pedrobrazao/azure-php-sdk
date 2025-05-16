<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Blob\Model;

final readonly class ContainerList implements \Countable
{
    /**
     * @param Container[] $containers
     */
    public function __construct(
        public string $prefix,
        public int $maxResults,
        public array $containers,
        public string $nextMarker
    ) {}

    public function count(): int
    {
        return count($this->containers);
    }

    public static function fromXml(\SimpleXMLElement $xml): self
    {
        $containers = [];

        foreach ($xml->Containers->children() as $container) {
            $containers[] = Container::fromXml($container);
        }

        return new self(
            (string) $xml->Prefix,
            (int) $xml->MaxResults,
            $containers,
            (string) $xml->NextMarker
        );
    }
}

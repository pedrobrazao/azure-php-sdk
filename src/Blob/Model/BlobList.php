<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Blob\Model;

final readonly class BlobList implements \Countable
{
    /**
     * @param Blob[] $blobs
     */
    public function __construct(
        public string $prefix,
        public int $maxResults,
        public array $blobs,
        public string $nextMarker
    ) {}

    public function count(): int
    {
        return count($this->blobs);
    }

    public static function fromXml(\SimpleXMLElement $xml): self
    {
        $blobs = [];

        foreach ($xml->Blobs->children() as $blob) {
            $blobs[] = Blob::fromXml($blob);
        }

        return new self(
            (string) $xml->Prefix,
            (int) $xml->MaxResults,
            $blobs,
            (string) $xml->NextMarker
        );
    }
}

<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Blob\Model;

final readonly class ContainerProperties
{
    public function __construct(
        public \DateTimeImmutable $lastModified,
        public string $etag,
        public string $leaseStatus,
        public string $leaseState,
        public bool $hasImmutabilityPolicy,
        public bool $hasLegalHold
    ) {}

    public static function fromXml(\SimpleXMLElement $xml): self
    {
        return new self(
            new \DateTimeImmutable((string) $xml->{'Last-Modified'}),
            (string) $xml->Etag,
            (string) $xml->LeaseStatus,
            (string) $xml->LeaseState,
            'true' === (string) $xml->HasImmutabilityPolicy,
            'true' === (string) $xml->HasLegalHold
        );
    }
}

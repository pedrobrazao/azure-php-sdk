<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Blob\Model;

use Psr\Http\Message\ResponseInterface;

final readonly class ContainerProperties
{
    public function __construct(
        public \DateTimeImmutable $lastModified,
        public ?Metadata $metadata = null,
        public ?string $etag = null,
        public ?string $leaseStatus = null,
        public ?string $leaseState = null,
        public ?bool $hasImmutabilityPolicy = null,
        public ?bool $hasLegalHold = null
    ) {}

    public static function fromXml(\SimpleXMLElement $xml): self
    {
        return new self(
            new \DateTimeImmutable((string) $xml->{'Last-Modified'}),
            null,
            (string) $xml->Etag,
            (string) $xml->LeaseStatus,
            (string) $xml->LeaseState,
            'true' === (string) $xml->HasImmutabilityPolicy,
            'true' === (string) $xml->HasLegalHold
        );
    }

    public static function fromResponse(ResponseInterface $response): self
    {
        return new self(
            new \DateTimeImmutable($response->getHeaderLine('Last-Modified')),
            Metadata::fromHeaders($response->getHeaders()),
        );
    }
}

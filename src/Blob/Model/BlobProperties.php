<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Blob\Model;

use AzurePhp\Storage\Common\Model\Metadata;
use Psr\Http\Message\ResponseInterface;

final readonly class BlobProperties
{
    public function __construct(
        public \DateTimeImmutable $lastModified,
        public int $contentLength,
        public string $contentType,
        public ?Metadata $metadata = null
    ) {}

    public static function fromXml(\SimpleXMLElement $xml): self
    {
        return new self(
            new \DateTimeImmutable((string) $xml->{'Last-Modified'}),
            (int) $xml->{'Content-Length'},
            (string) $xml->{'Content-Type'}
        );
    }

    public static function fromResponse(ResponseInterface $response): self
    {
        return new self(
            new \DateTimeImmutable($response->getHeaderLine('Last-Modified')),
            (int) $response->getHeaderLine('Content-Length'),
            $response->getHeaderLine('Content-Type'),
            Metadata::fromHeaders($response->getHeaders())
        );
    }
}

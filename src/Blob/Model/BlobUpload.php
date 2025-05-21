<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Blob\Model;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;

final readonly class BlobUpload
{
    public function __construct(
        public StreamInterface $stream,
        public string $contentType,
        public int $initialTransferSize = 256_000_000,
        public int $maximumTransferSize = 8_000_000,
        public int $maximumConcurrency = 25
    ) {}

    public static function fromString(string $contents, ?string $contentType = null): self
    {
        if (null === $contentType && class_exists('finfo')) {
            $contentType = (new \finfo(FILEINFO_MIME))->buffer($contents);
        }

        if (false === is_string($contentType)) {
            $contentType = 'application/octet-stream';
        }

        return new self(Utils::streamFor($contents), $contentType);
    }
}

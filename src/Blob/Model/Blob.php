<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Blob\Model;

use Psr\Http\Message\StreamInterface;

final readonly class Blob implements \Stringable
{
    public function __construct(
        public string $name,
        public BlobProperties $properties,
        public ?StreamInterface $stream = null
    ) {}

    public function __toString(): string
    {
        if (null === $this->stream) {
            return '';
        }

        return $this->stream->getContents();
    }
}

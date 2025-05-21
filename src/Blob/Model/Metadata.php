<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Blob\Model;

final class Metadata implements \Countable
{
    public const HEADER_PREFIX = 'x-ms-meta-';

    /**
     * @param string[] $metadata
     */
    public function __construct(
        private array $metadata = []
    ) {}

    public function count(): int
    {
        return count($this->metadata);
    }

    /**
     * @return string[]
     */
    public function toArray(): array
    {
        return $this->metadata;
    }

    public function push(string $key, string $value): self
    {
        $this->metadata[$key] = $value;

        return $this;
    }

    /**
     * @param array<string, string[]> $headers
     */
    public static function fromHeaders(array $headers): self
    {
        $metadata = [];

        foreach ($headers as $key => $values) {
            if (str_starts_with($key, self::HEADER_PREFIX)) {
                $metadata[substr($key, strlen(self::HEADER_PREFIX))] = implode('; ', $values);
            }
        }

        return new self($metadata);
    }

    /**
     * @return string[]
     */
    public function toHeaders(): array
    {
        $headers = [];

        foreach ($this->metadata as $name => $value) {
            $headers[self::HEADER_PREFIX.$name] = $value;
        }

        return $headers;
    }
}

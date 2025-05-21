<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Blob\Model;

use Psr\Http\Message\StreamInterface;

final readonly class UploadBlock
{
    public const UNCOMMITTED = 'uncommitted';
    public const COMMITTED = 'committed';
    public const LATEST = 'latest';

    public function __construct(
        public StreamInterface $contents,
        public int $id,
        public string $type = self::UNCOMMITTED
    ) {}

    public function getId(): string
    {
        return base64_encode(str_pad((string) $this->id, 6, '0', STR_PAD_LEFT));
    }
}

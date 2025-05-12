<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Common\Auth;

final readonly class SharedAccountKey
{
    public function __construct(
        public string $accountName,
        public string $accountKey
    ) {}
}

<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Common\Client;

use AzurePhp\Storage\Common\Auth\SharedAccountKey;
use Psr\Http\Message\RequestInterface;

final readonly class HeaderAuthorizationMiddleware
{
    public const HEADER_NAME = 'authorization';

    public function __construct(private SharedAccountKey $key) {}

    public function __invoke(callable $handler): \Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $signature = $this->key->getSignature($request);
            $request = $request->withHeader(self::HEADER_NAME, $signature);

            return $handler($request, $options);
        };
    }
}

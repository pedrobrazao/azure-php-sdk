<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Common\Client;

use Psr\Http\Message\RequestInterface;

final readonly class HeaderClientRequestIdMiddleware
{
    public const HEADER_NAME = 'x-ms-client-request-id';

    public function __invoke(callable $handler): \Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $request = $request->withHeader(self::HEADER_NAME, bin2hex(random_bytes(16)));

            return $handler($request, $options);
        };
    }
}

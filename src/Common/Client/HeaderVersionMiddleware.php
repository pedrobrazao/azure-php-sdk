<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Common\Client;

use AzurePhp\Storage\Common\ApiVersion;
use Psr\Http\Message\RequestInterface;

final class HeaderVersionMiddleware
{
    public const HEADER_NAME = 'x-ms-version';

    public function __invoke(callable $handler): \Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $request = $request->withHeader(self::HEADER_NAME, ApiVersion::LATEST);

            return $handler($request, $options);
        };
    }
}

<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Common\Client;

use Psr\Http\Message\RequestInterface;

final class HeaderDateMiddleware
{
    public const HEADER_NAME = 'x-ms-date';

    public function __invoke(callable $handler): \Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $request = $request->withHeader(self::HEADER_NAME, gmdate('D, d M Y H:i:s T', time()));

            return $handler($request, $options);
        };
    }
}

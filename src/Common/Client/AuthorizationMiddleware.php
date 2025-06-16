<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Common\Client;

use AzurePhp\Storage\Common\Auth\SharedAccessKeyInterface;
use Psr\Http\Message\RequestInterface;

final readonly class AuthorizationMiddleware
{
    public const AUTHORIZATION = 'Authorization';

    public function __construct(
        private SharedAccessKeyInterface $accessKey
    ) {}

    public function __invoke(callable $handler): \Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $request = $request->withHeader(self::AUTHORIZATION, $this->accessKey->getAuthorization($request));

            return $handler($request, $options);
        };
    }
}

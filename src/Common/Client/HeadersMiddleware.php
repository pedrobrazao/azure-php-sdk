<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Common\Client;

use Psr\Http\Message\RequestInterface;

final readonly class HeadersMiddleware
{
    public const X_MS_DATE = 'x-ms-date';
    public const X_MS_CLIENT_REQUEST_ID = 'x-ms-client-request-id';

    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private array $headers
    ) {}

    public function __invoke(callable $handler): \Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            foreach ($this->headers as $name => $value) {
                if ($request->hasHeader($name)) {
                    continue;
                }

                $request = $request->withHeader($name, $value);
            }

            if (false === $request->hasHeader(self::X_MS_CLIENT_REQUEST_ID)) {
                $request = $request->withHeader(self::X_MS_CLIENT_REQUEST_ID, bin2hex(random_bytes(16)));
            }

            $date = gmdate('D, d M Y H:i:s T', time());
            $request = $request->withHeader(self::X_MS_DATE, $date)->withHeader('date', $date);

            return $handler($request, $options);
        };
    }
}

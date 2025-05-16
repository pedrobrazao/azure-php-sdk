<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Common\Client;

use AzurePhp\Storage\Common\Auth\SharedAccountKey;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleRetry\GuzzleRetryMiddleware;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\UriInterface;

final readonly class ClientFactory
{
    public function __construct(
        private UriInterface $uri,
        private ?SharedAccountKey $key = null
    ) {}

    public function create(): ClientInterface
    {
        $stack = HandlerStack::create();

        $stack->push(new HeaderClientRequestIdMiddleware());
        $stack->push(new HeaderDateMiddleware());
        $stack->push(new HeaderVersionMiddleware());
        $stack->push(new QueryStringMiddleware($this->uri->getQuery()));

        if (null !== $this->key) {
            $stack->push(new HeaderAuthorizationMiddleware($this->key));
        }

        // @see https://learn.microsoft.com/en-us/azure/architecture/best-practices/retry-service-specific#general-rest-and-retry-guidelines
        $stack->push(GuzzleRetryMiddleware::factory([
            'retry_on_status' => [
                408, // Request Timeout
                429, // Too Many Requests
                500, // Internal Server Error
                502, // Bad Gateway
                503, // Service Unavailable
                504, // Gateway Timeout
            ],
        ]));

        return new Client(['handler' => $stack]);
    }
}

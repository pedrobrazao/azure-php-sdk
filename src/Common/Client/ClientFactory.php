<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Common\Client;

use AzurePhp\Storage\Common\Auth\SharedAccessKeyInterface;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleRetry\GuzzleRetryMiddleware;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\UriInterface;

final readonly class ClientFactory
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private UriInterface $uri,
        private array $headers,
        private ?SharedAccessKeyInterface $accessKey = null
    ) {}

    /**
     * @param array<callable|scalar|scalar[]> $options
     *
     * @see https://docs.guzzlephp.org/en/stable/request-options.html
     */
    public function create(array $options = []): ClientInterface
    {
        $handler = $options['handler'] ?? HandlerStack::create();

        if (false === $handler instanceof HandlerStack) {
            $handler = HandlerStack::create($handler);
        }

        if ('' !== $defaultQuery =  $this->uri->getQuery()) {
            $handler->push(new QueryStringMiddleware($defaultQuery));
        }

        $handler->push(new HeadersMiddleware($this->headers));

        if (null !== $this->accessKey) {
            $handler->push(new AuthorizationMiddleware($this->accessKey));
        }

        // @see https://learn.microsoft.com/en-us/azure/architecture/best-practices/retry-service-specific#general-rest-and-retry-guidelines
        $handler->push(GuzzleRetryMiddleware::factory([
            'retry_on_status' => [
                408, // Request Timeout
                429, // Too Many Requests
                500, // Internal Server Error
                502, // Bad Gateway
                503, // Service Unavailable
                504, // Gateway Timeout
            ],
        ]));

        $options['handler'] = $handler;

        return new Client($options);
    }
}

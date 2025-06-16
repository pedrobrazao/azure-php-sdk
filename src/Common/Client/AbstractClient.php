<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Common\Client;

use AzurePhp\Storage\Common\Auth\ConnectionStringParser;
use AzurePhp\Storage\Common\Exception\InvalidConnectionStringException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\ClientTrait;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

abstract readonly class AbstractClient
{
    use ClientTrait;

    public const STORAGE_API_LATEST_VERSION = '2025-05-05';

    final public function __construct(
        protected ClientInterface $client,
        protected UriInterface $uri
    ) {}

    /**
     * @param array<string, string>           $headers
     * @param array<callable|scalar|scalar[]> $options
     */
    protected static function factory(string $connectionString, string $serviceName, array $headers, string $accessKeyClass, array $options = []): static
    {
        $parser = ConnectionStringParser::parse($connectionString);
        $getRndpointMethod = sprintf('get%sEndpoint', ucfirst(strtolower($serviceName)));

        /** @var UriInterface $uri */
        $uri = $parser->{$getRndpointMethod}();

        if (!str_ends_with($uri->getPath(), '/')) {
            $uri = $uri->withPath($uri->getPath().'/');
        }

        $headers['x-ms-version'] = self::STORAGE_API_LATEST_VERSION;

        if ($parser->isSasEndpoint()) {
            $client = (new ClientFactory($uri, $headers))->create($options);

            return new static($client, $uri);
        }

        $accountName = $parser->getAccountName();
        $accountKey = $parser->getAccountKey();

        if (null !== $accountName && null !== $accountKey) {
            $client = (new ClientFactory($uri, $headers, new $accessKeyClass($accountName, $accountKey)))->create($options);

            return new static($client, $uri);
        }

        throw new InvalidConnectionStringException('Missing Account Name and/or Account Key.');
    }

    /**
     * @param string|UriInterface             $uri
     * @param array<callable|scalar|scalar[]> $options
     */
    public function request(string $method, $uri, array $options = []): ResponseInterface
    {
        return $this->client->request($method, $uri, $options);
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * @param string|UriInterface             $uri
     * @param array<callable|scalar|scalar[]> $options
     */
    public function requestAsync(string $method, $uri, array $options = []): PromiseInterface
    {
        return $this->client->requestAsync($method, $uri, $options);
    }
}

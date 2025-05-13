<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Common\Auth;

use AzurePhp\Storage\Common\Exception\InvalidConnectionStringException;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

/**
 * @internal
 *
 * @see https://learn.microsoft.com/en-us/azure/storage/common/storage-configure-connection-string
 */
final class ConnectionStringParser
{
    /** @var UriInterface[] */
    private array $endpoints = [];

    /**
     * @param string[] $parts
     */
    public function __construct(private readonly array $parts = []) {}

    public static function parse(string $connectionString): self
    {
        $parts = [];

        foreach (explode(';', $connectionString) as $part) {
            if (false === str_contains($part, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $part, 2);

            $parts[$name] = $value;
        }

        return new self($parts);
    }

    public function getBlobEndpoint(): UriInterface
    {
        return $this->getServiceEndpoint('blob');
    }

    public function getQueueEndpoint(): UriInterface
    {
        return $this->getServiceEndpoint('queue');
    }

    public function getTableEndpoint(): UriInterface
    {
        return $this->getServiceEndpoint('table');
    }

    private function getServiceEndpoint(string $name): UriInterface
    {
        if (isset($this->endpoints[$name])) {
            return $this->endpoints[$name];
        }

        if (isset($this->parts[ucfirst($name).'Endpoint'])) {
            $this->endpoints[$name] = new Uri($this->parts[ucfirst($name).'Endpoint']);
        }

        if (false === isset($this->endpoints[$name]) && isset($this->parts['AccountName'], $this->parts['EndpointSuffix'])) {
            $this->endpoints[$name] = new Uri(sprintf('https://%s.%s.%s', $this->parts['AccountName'], $name, $this->parts['EndpointSuffix']));
        }

        if (false === isset($this->endpoints[$name])) {
            throw new InvalidConnectionStringException(sprintf('Missing endpoint for "%s" service.', $name));
        }

        if (isset($this->parts['DefaultEndpointsProtocol'])) {
            $this->endpoints[$name] = $this->endpoints[$name]->withScheme($this->parts['DefaultEndpointsProtocol']);
        }

        if (isset($this->parts['SharedAccessSignature'])) {
            $this->endpoints[$name] = $this->endpoints[$name]->withQuery($this->parts['SharedAccessSignature']);
        }

        return $this->endpoints[$name];
    }

    public function isSasEndpoint(): bool
    {
        return isset($this->parts['SharedAccessSignature']);
    }

    public function getAccountName(): ?string
    {
        return  $this->parts['AccountName'] ?? null;
    }

    public function getAccountKey(): ?string
    {
        return  $this->parts['AccountKey'] ?? null;
    }
}

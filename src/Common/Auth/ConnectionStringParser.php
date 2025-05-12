<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Common\Auth;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

/**
 * @internal
 *
 * @see https://learn.microsoft.com/en-us/azure/storage/common/storage-configure-connection-string
 */
final class ConnectionStringParser
{
    public const LOCAL_BLOB_ENDPOINT = 'http://127.0.0.1:10000/devstoreaccount1';
    public const LOCAL_QUEUE_ENDPOINT = 'http://127.0.0.1:10001/devstoreaccount1';
    public const LOCAL_TABLE_ENDPOINT = 'http://127.0.0.1:10002/devstoreaccount1';
    public const LOCAL_ACCOUNT_NAME = 'devstoreaccount1';
    public const LOCAL_ACCOUNT_KEY = 'Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==';

    /** @var string[] */
    private array $localEndpoints = [
        'blob' => self::LOCAL_BLOB_ENDPOINT,
        'queue' => self::LOCAL_QUEUE_ENDPOINT,
        'table' => self::LOCAL_TABLE_ENDPOINT,
    ];

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
            if (!str_contains($part, '=')) {
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
            $this->endpoints[$name] = new Uri($this->localEndpoints[$name]);
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

    public function getAccountName(): string
    {
        return  $this->parts['AccountName'] ?? self::LOCAL_ACCOUNT_NAME;
    }

    public function getAccountKey(): string
    {
        return  $this->parts['AccountKey'] ?? self::LOCAL_ACCOUNT_KEY;
    }
}

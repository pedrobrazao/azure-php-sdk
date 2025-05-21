<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Blob;

use AzurePhp\Storage\Blob\Model\Container;
use AzurePhp\Storage\Blob\Model\ContainerList;
use AzurePhp\Storage\Common\Auth\ConnectionStringParser;
use AzurePhp\Storage\Common\Auth\SharedAccountKey;
use AzurePhp\Storage\Common\Client\ClientFactory;
use AzurePhp\Storage\Common\Exception\InvalidConnectionStringException;
use GuzzleHttp\Psr7\Query;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\UriInterface;

final readonly class AccountClient
{
    public function __construct(
        private ClientInterface $client,
        private UriInterface $uri
    ) {}

    public static function fromConnectionString(string $connectionString): self
    {
        $parser = ConnectionStringParser::parse($connectionString);
        $uri = $parser->getBlobEndpoint();
        $accountName = $parser->getAccountName();
        $accountKey = $parser->getAccountKey();

        if ($parser->isSasEndpoint() || (null === $accountName && null === $accountKey)) {
            return new self((new ClientFactory($uri))->create(), $uri);
        }

        if (null !== $accountName && null !== $accountKey) {
            return new self((new ClientFactory($uri, new SharedAccountKey($accountName, $accountKey)))->create(), $uri);
        }

        throw new InvalidConnectionStringException('Missing "AccountName" and/or "AccountKey" parameters.');
    }

    public function getContainerClient(string $containerName): ContainerClient
    {
        $path = sprintf('%s/%s', rtrim($this->uri->getPath(), '/'), trim($containerName, '/'));
        $uri = $this->uri->withPath($path);

        return new ContainerClient($this->client, $uri);
    }

    /**
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/list-containers2?tabs=microsoft-entra-id
     *
     * @return \Generator<Container>
     */
    public function listContainers(string $prefix = ''): \Generator
    {
        $query = ['comp' => 'list', 'prefix' => $prefix];
        $nextMarker = '';

        while (true) {
            if ('' !== $nextMarker) {
                $query['marker'] = $nextMarker;
            }

            $uri = $this->uri->withQuery(Query::build($query));
            $response = $this->client->send(new Request('GET', $uri));
            $xml = new \SimpleXMLElement($response->getBody()->getContents());
            $containerList = ContainerList::fromXml($xml);

            foreach ($containerList->containers as $container) {
                yield $container;
            }

            $nextMarker = $containerList->nextMarker;

            if ('' === $nextMarker) {
                break;
            }
        }
    }
}

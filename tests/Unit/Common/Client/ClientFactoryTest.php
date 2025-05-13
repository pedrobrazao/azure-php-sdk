<?php

declare(strict_types=1);

namespace AzurePhp\Tests\Storage\Unit\Common\Client;

use AzurePhp\Storage\Common\Auth\ConnectionStringParser;
use AzurePhp\Storage\Common\Auth\SharedAccountKey;
use AzurePhp\Storage\Common\Client\ClientFactory;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ClientFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $uri = new Uri(ConnectionStringParser::LOCAL_BLOB_ENDPOINT);
        $key = new SharedAccountKey(ConnectionStringParser::LOCAL_ACCOUNT_NAME, ConnectionStringParser::LOCAL_ACCOUNT_KEY);
        $client = (new ClientFactory($uri, $key))->create();

        $this->assertInstanceOf(ClientInterface::class, $client);
    }
}

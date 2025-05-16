<?php

declare(strict_types=1);

namespace AzurePhp\Tests\Storage\Unit\Common\Client;

use AzurePhp\Storage\Common\Auth\SharedAccountKey;
use AzurePhp\Storage\Common\Client\ClientFactory;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;

/**
 * @internal
 *
 * @coversNothing
 */
final class ClientFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $uri = new Uri($_ENV['AZURE_STORAGE_BLOB_ENDPOINT']);
        $key = new SharedAccountKey($_ENV['AZURE_STORAGE_ACCOUNT_NAME'], $_ENV['AZURE_STORAGE_ACCOUNT_KEY']);
        $client = (new ClientFactory($uri, $key))->create();

        $this->assertInstanceOf(ClientInterface::class, $client);
    }
}

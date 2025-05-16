<?php

declare(strict_types=1);

namespace AzurePhp\Tests\Storage\Unit\Blob;

use AzurePhp\Storage\Blob\AccountClient;
use AzurePhp\Storage\Common\Exception\InvalidConnectionStringException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class AccountClientTest extends TestCase
{
    public function testInvalidConnectionString(): void
    {

        $connectionString = sprintf('BlobEndpoint=%s;AccountName=%s', $_ENV['AZURE_STORAGE_BLOB_ENDPOINT'], $_ENV['AZURE_STORAGE_ACCOUNT_NAME']);

        $this->expectException(InvalidConnectionStringException::class);
        AccountClient::fromConnectionString($connectionString);
    }

    #[DataProvider('connectionStringProvider')]
    public function testFromConnectionString(string $connectionString): void
    {
        $client = AccountClient::fromConnectionString($connectionString);

        $this->assertInstanceOf(AccountClient::class, $client);
    }

    /**
     * @return array<string[]>
     */
    public static function connectionStringProvider(): array
    {
        return [
            [sprintf('BlobEndpoint=%s;AccountName=%s;AccountKey=%s', $_ENV['AZURE_STORAGE_BLOB_ENDPOINT'], $_ENV['AZURE_STORAGE_ACCOUNT_NAME'], $_ENV['AZURE_STORAGE_ACCOUNT_KEY'])],
            [sprintf('BlobEndpoint=%s;SharedAccessSignature=foo=bar', $_ENV['AZURE_STORAGE_BLOB_ENDPOINT'])],
        ];
    }
}

<?php

declare(strict_types=1);

namespace AzurePhp\Tests\Storage\Integration;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
abstract class AbstractIntegrationTestCase extends TestCase
{
    public function getLocalConnectionString(): string
    {
        return sprintf(
            'BlobEndpoint=%s;QueueEndpoint=%s;TableEndpoint=%s;AccountName=%s;AccountKey=%s',
            $_ENV['AZURE_STORAGE_BLOB_ENDPOINT'],
            $_ENV['AZURE_STORAGE_QUEUE_ENDPOINT'],
            $_ENV['AZURE_STORAGE_TABLE_ENDPOINT'],
            $_ENV['AZURE_STORAGE_ACCOUNT_NAME'],
            $_ENV['AZURE_STORAGE_ACCOUNT_KEY'],
        );
    }
}

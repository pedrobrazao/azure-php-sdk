<?php

declare(strict_types=1);

namespace AzurePhp\Tests\Storage\Integration;

trait IntegrationTrait
{
    public function getConnectionString(): string
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

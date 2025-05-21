<?php

declare(strict_types=1);

namespace AzurePhp\Tests\Storage\Integration\Blob;

use AzurePhp\Storage\Blob\AccountClient;
use AzurePhp\Tests\Storage\Integration\AbstractIntegrationTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ContainerClientTest extends AbstractIntegrationTestCase
{
    public function testContainerContainer(): void
    {
        $containerName = uniqid('test-');

        $client = AccountClient::fromConnectionString($this->getLocalConnectionString())->getContainerClient($containerName);
        $this->assertFalse($client->exists());

        $client->create();
        $this->assertTrue($client->exists());

        $client->delete();
        $this->assertFalse($client->exists());
    }
}

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
final class AccountClientTest extends AbstractIntegrationTestCase
{
    public function testListContainers(): void
    {
        $client = AccountClient::fromConnectionString($this->getLocalConnectionString());
        $containers = [];

        foreach ($client->listContainers() as $container) {
            $containers[] = $container;
        }

        $this->assertCount(3, $containers);
    }
}

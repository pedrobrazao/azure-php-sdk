<?php

declare(strict_types=1);

namespace AzurePhp\Tests\Storage\Integration\Blob;

use AzurePhp\Storage\Blob\AccountClient;
use AzurePhp\Storage\Blob\Model\Container;
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
        $names = [];

        for ($i = 0; $i < 3; ++$i) {
            $name = uniqid('test-');
            $names[] = $name;
            $client->getContainerClient($name)->create();
        }

        $containers = [];

        foreach ($client->listContainers() as $container) {
            $containers[$container->name] = $container;
        }

        foreach ($names as $name) {
            $this->assertArrayHasKey($name, $containers);
            $this->assertInstanceOf(Container::class, $containers[$name]);
            $client->getContainerClient($name)->delete();
        }
    }
}

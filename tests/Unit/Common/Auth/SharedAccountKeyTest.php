<?php

declare(strict_types=1);

namespace AzurePhp\Tests\Storage\Unit\Common\Auth;

use AzurePhp\Storage\Common\Auth\SharedAccountKey;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class SharedAccountKeyTest extends TestCase
{
    public function testProperties(): void
    {
        $accountName = $_ENV['AZURE_STORAGE_ACCOUNT_NAME'];
        $accountKey = $_ENV['AZURE_STORAGE_ACCOUNT_KEY'];
        $instance = new SharedAccountKey($accountName, $accountKey);

        $this->assertSame($accountName, $instance->accountName);
        $this->assertSame($accountKey, $instance->accountKey);
    }
}

<?php

declare(strict_types=1);

namespace AzurePhp\Tests\Storage\Unit\Blob\Model;

use AzurePhp\Storage\Blob\Model\Metadata;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class MetadataTest extends TestCase
{
    public function testFromHeaders(): void
    {
        $headers = [
            Metadata::HEADER_PREFIX.'key1' => ['value1'],
            Metadata::HEADER_PREFIX.'key2' => ['valu2'],
            Metadata::HEADER_PREFIX.'key3' => ['valu3'],
        ];

        $metadata = Metadata::fromHeaders($headers);

        $this->assertCount(3, $metadata);

        foreach ($headers as $name => $value) {
            $key = substr($name, strlen(Metadata::HEADER_PREFIX));
            $this->assertArrayHasKey($key, $metadata->toArray());
            $this->assertSame($value[0], $metadata->toArray()[$key]);
            $this->assertArrayHasKey($name, $metadata->toHeaders());
            $this->assertSame($value[0], $metadata->toHeaders()[$name]);
        }
    }
}

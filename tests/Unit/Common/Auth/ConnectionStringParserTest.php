<?php

declare(strict_types=1);

namespace AzurePhp\Tests\Storage\Unit\Common\Auth;

use AzurePhp\Storage\Common\Auth\ConnectionStringParser;
use AzurePhp\Storage\Common\Exception\InvalidConnectionStringException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ConnectionStringParserTest extends TestCase
{
    #[DataProvider('serviceNameProvider')]
    public function testMissingServiceEndpoint(string $name): void
    {
        $parser = ConnectionStringParser::parse('');
        $method = sprintf('get%sEndpoint', $name);

        $this->expectException(InvalidConnectionStringException::class);
        $parser->{$method}();
    }

    /**
     * @return array<string[]>
     */
    public static function serviceNameProvider(): array
    {
        return [
            ['blob'],
            ['queue'],
            ['table'],
        ];
    }

    #[DataProvider('serviceEndpointProvider')]
    public function testGetServiceEndpoint(string $connectionString, string $blobEndpoint, string $queueEndpoint, string $tableEndpoint): void
    {
        $parser = ConnectionStringParser::parse($connectionString);

        $this->assertSame($blobEndpoint, (string) $parser->getBlobEndpoint());
        $this->assertSame($queueEndpoint, (string) $parser->getQueueEndpoint());
        $this->assertSame($tableEndpoint, (string) $parser->getTableEndpoint());
    }

    /**
     * @return array<string[]>
     */
    public static function serviceEndpointProvider(): array
    {
        return [
            ['BlobEndpoint=http://localhost:10000/devstoreaccount1;QueueEndpoint=http://localhost:10001/devstoreaccount1;TableEndpoint=http://localhost:10002/devstoreaccount1', 'http://localhost:10000/devstoreaccount1', 'http://localhost:10001/devstoreaccount1', 'http://localhost:10002/devstoreaccount1'],
            ['BlobEndpoint=http://localhost:10000/devstoreaccount1;QueueEndpoint=http://localhost:10001/devstoreaccount1;TableEndpoint=http://localhost:10002/devstoreaccount1;DefaultEndpointsProtocol=https', 'https://localhost:10000/devstoreaccount1', 'https://localhost:10001/devstoreaccount1', 'https://localhost:10002/devstoreaccount1'],
            ['EndpointSuffix=core.windows.net;AccountName=devstoreaccount1', 'https://devstoreaccount1.blob.core.windows.net', 'https://devstoreaccount1.queue.core.windows.net', 'https://devstoreaccount1.table.core.windows.net'],
            [
                'SharedAccessSignature=sv=2015-07-08&sig=iCvQmdZngZNW%2F4vw43j6%2BVz6fndHF5LI639QJba4r8o%3D&spr=https&st=2016-04-12T03%3A24%3A31Z&se=2016-04-13T03%3A29%3A31Z&srt=s&ss=bqt&sp=rwl;BlobEndpoint=http://127.0.0.1:10000/devstoreaccount1;QueueEndpoint=http://127.0.0.1:10001/devstoreaccount1;TableEndpoint=http://127.0.0.1:10002/devstoreaccount1',
                'http://127.0.0.1:10000/devstoreaccount1?sv=2015-07-08&sig=iCvQmdZngZNW%2F4vw43j6%2BVz6fndHF5LI639QJba4r8o%3D&spr=https&st=2016-04-12T03%3A24%3A31Z&se=2016-04-13T03%3A29%3A31Z&srt=s&ss=bqt&sp=rwl',
                'http://127.0.0.1:10001/devstoreaccount1?sv=2015-07-08&sig=iCvQmdZngZNW%2F4vw43j6%2BVz6fndHF5LI639QJba4r8o%3D&spr=https&st=2016-04-12T03%3A24%3A31Z&se=2016-04-13T03%3A29%3A31Z&srt=s&ss=bqt&sp=rwl',
                'http://127.0.0.1:10002/devstoreaccount1?sv=2015-07-08&sig=iCvQmdZngZNW%2F4vw43j6%2BVz6fndHF5LI639QJba4r8o%3D&spr=https&st=2016-04-12T03%3A24%3A31Z&se=2016-04-13T03%3A29%3A31Z&srt=s&ss=bqt&sp=rwl',
            ],
        ];
    }

    /**
     * @param string[] $parts
     */
    #[DataProvider('isSasProvider')]
    public function testIsSasEndpoint(array $parts, bool $isSas): void
    {
        $parser = new ConnectionStringParser($parts);

        $this->assertSame($isSas, $parser->isSasEndpoint());
    }

    /**
     * @return array<array<bool|string[]>>
     */
    public static function isSasProvider(): array
    {
        return [
            [[], false],
            [['SharedAccessSignature' => 'sas-signature'], true],
        ];
    }

    /**
     * @param string[] $parts
     */
    #[DataProvider('accountNameProvider')]
    public function testGetAccountName(array $parts, ?string $accountName): void
    {
        $parser = new ConnectionStringParser($parts);

        $this->assertSame($accountName, $parser->getAccountName());
    }

    /**
     * @return array<array<null|string|string[]>>
     */
    public static function accountNameProvider(): array
    {
        return [
            [[], null],
            [['AccountName' => 'myaccount'], 'myaccount'],
        ];
    }

    /**
     * @param string[] $parts
     */
    #[DataProvider('accountKeyProvider')]
    public function testGetAccountKey(array $parts, ?string $accountKey): void
    {
        $parser = new ConnectionStringParser($parts);

        $this->assertSame($accountKey, $parser->getAccountKey());
    }

    /**
     * @return array<array<null|string|string[]>>
     */
    public static function accountKeyProvider(): array
    {
        return [
            [[], null],
            [['AccountKey' => 'account-key'], 'account-key'],
        ];
    }
}

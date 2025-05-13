<?php

declare(strict_types=1);

namespace AzurePhp\Tests\Storage\Unit\Common\Client;

use AzurePhp\Storage\Common\Auth\ConnectionStringParser;
use AzurePhp\Storage\Common\Auth\SharedAccountKey;
use AzurePhp\Storage\Common\Client\HeaderAuthorizationMiddleware;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

/**
 * @internal
 *
 * @coversNothing
 */
final class HeaderAuthorizationMiddlewareTest extends TestCase
{
    public function testInvoke(): void
    {
        $key = new SharedAccountKey(ConnectionStringParser::LOCAL_ACCOUNT_NAME, ConnectionStringParser::LOCAL_ACCOUNT_KEY);
        $middleware = new HeaderAuthorizationMiddleware($key);

        // fake handler; only for tests
        $handler = (fn (RequestInterface $request, array $options) => $request);

        $fn = $middleware($handler);

        $request = new Request('GET', 'http://example.org/');
        $result = $fn($request, []);

        $this->assertInstanceOf(RequestInterface::class, $result);
        $this->assertCount(1, $result->getHeader(HeaderAuthorizationMiddleware::HEADER_NAME));
    }
}

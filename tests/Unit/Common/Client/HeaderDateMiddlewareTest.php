<?php

declare(strict_types=1);

namespace AzurePhp\Tests\Storage\Unit\Common\Client;

use AzurePhp\Storage\Common\Client\HeaderDateMiddleware;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

/**
 * @internal
 *
 * @coversNothing
 */
final class HeaderDateMiddlewareTest extends TestCase
{
    public function testInvoke(): void
    {
        $middleware = new HeaderDateMiddleware();

        // fake handler; only for tests
        $handler = (fn (RequestInterface $request, array $options) => $request);

        $fn = $middleware($handler);

        $request = new Request('GET', 'http://example.org/');
        $result = $fn($request, []);

        $this->assertInstanceOf(RequestInterface::class, $result);
        $this->assertCount(1, $result->getHeader(HeaderDateMiddleware::HEADER_NAME));
    }
}

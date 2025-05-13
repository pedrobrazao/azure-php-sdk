<?php

declare(strict_types=1);

namespace AzurePhp\Tests\Storage\Unit\Common\Client;

use AzurePhp\Storage\Common\Client\HeaderClientRequestIdMiddleware;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

/**
 * @internal
 *
 * @coversNothing
 */
final class HeaderClientRequestIdMiddlewareTest extends TestCase
{
    public function testInvoke(): void
    {
        $middleware = new HeaderClientRequestIdMiddleware();

        // fake handler; only for tests
        $handler = (fn (RequestInterface $request, array $options) => $request);

        $fn = $middleware($handler);

        $request = new Request('GET', 'http://example.org/');
        $result = $fn($request, []);

        $this->assertInstanceOf(RequestInterface::class, $result);
        $this->assertCount(1, $result->getHeader(HeaderClientRequestIdMiddleware::HEADER_NAME));
    }
}

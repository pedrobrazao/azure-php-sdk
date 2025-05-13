<?php

declare(strict_types=1);

namespace AzurePhp\Tests\Storage\Unit\Common\Client;

use AzurePhp\Storage\Common\Client\QueryStringMiddleware;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

/**
 * @internal
 *
 * @coversNothing
 */
final class QueryStringMiddlewareTest extends TestCase
{
    public function testInvoke(): void
    {
        $defaultQuery = 'foo=bar&baz=zaa';
        $middleware = new QueryStringMiddleware($defaultQuery);

        // fake handler; only for tests
        $handler = (fn (RequestInterface $request, array $options) => $request);

        $fn = $middleware($handler);

        $request = new Request('GET', 'http://example.org/?cal=qee');
        $result = $fn($request, []);

        $this->assertInstanceOf(RequestInterface::class, $result);
        $this->assertSame($defaultQuery.'&cal=qee', $result->getUri()->getQuery());
    }
}

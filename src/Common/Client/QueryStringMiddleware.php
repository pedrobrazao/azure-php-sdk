<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Common\Client;

use GuzzleHttp\Psr7\Query;
use Psr\Http\Message\RequestInterface;

final readonly class QueryStringMiddleware
{
    public function __construct(private string $defaultQuery) {}

    public function __invoke(callable $handler): \Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $query = Query::build([
                ...Query::parse($this->defaultQuery),
                ...Query::parse($request->getUri()->getQuery()),
            ]);

            $uri = $request->getUri()->withQuery($query);
            $request = $request->withUri($uri);

            return $handler($request, $options);
        };
    }
}

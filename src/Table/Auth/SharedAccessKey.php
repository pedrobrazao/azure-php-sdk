<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Table\Auth;

use AzurePhp\Storage\Common\Auth\AbstractSharedAccessKey;
use GuzzleHttp\Psr7\Query;
use Psr\Http\Message\RequestInterface;

final readonly class SharedAccessKey extends AbstractSharedAccessKey
{
    public function getAuthorization(RequestInterface $request): string
    {
        $signature = $this->getSignature($request);

        return sprintf('SharedKeyLite %s:%s', $this->accountName, $signature);
    }

    /**
     * @return string[]
     */
    protected function getIncludedHeaders(): array
    {
        return ['date'];
    }

    protected function getStringToSign(RequestInterface $request): string
    {

        $headers = array_map(fn ($value) => implode(', ', $value), $request->getHeaders());

        if (isset($headers['Content-Length']) && '0' === $headers['Content-Length']) {
            $headers['Content-Length'] = '';
        }

        /** @var array<string> $query */
        $query = Query::parse($request->getUri()->getQuery());
        $url = (string) $request->getUri();

        $stringToSign = [];

        foreach ($this->getIncludedHeaders() as $header) {
            $stringToSign[] = array_change_key_case($headers)[strtolower($header)] ?? null;
        }

        $stringToSign[] = $this->computeCanonicalizedResource($url, $query);

        return implode("\n", $stringToSign);
    }

    /**
     * @param array<string> $queryParams
     */
    protected function computeCanonicalizedResource(string $url, array $queryParams): string
    {
        $queryParams = array_change_key_case($queryParams);

        // 1. Beginning with an empty string (""), append a forward slash (/),
        //    followed by the name of the account that owns the accessed resource.
        $canonicalizedResource = '/'.$this->accountName;

        // 2. Append the resource's encoded URI path, without any query parameters.
        $canonicalizedResource .= parse_url($url, PHP_URL_PATH);

        // 3. The query string should include the question mark and the comp
        //    parameter (for example, ?comp=metadata). No other parameters should
        //    be included on the query string.
        if (array_key_exists('comp', $queryParams)) {
            $canonicalizedResource .= sprintf('?comp=%s', $queryParams['comp']);
        }

        return $canonicalizedResource;
    }
}

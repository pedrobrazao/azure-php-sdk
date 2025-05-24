<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Queue;

use AzurePhp\Storage\Queue\Model\Message;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Query;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\UriInterface;

final readonly class MessageClient
{
    private const MESSAGE_TEMPLATE = '<QueueMessage><MessageText>%s</MessageText></QueueMessage>';

    public function __construct(
        private ClientInterface $client,
        private UriInterface $uri
    ) {}

    /**
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/put-message
     */
    public function put(string $text, int $ttl = -1, int $visibility = 0): Message
    {
        $uri = $this->uri->withQuery(Query::build(['visibilitytimeout' => $visibility, 'messagettl' => $ttl]));
        $message = (new \SimpleXMLElement(sprintf(self::MESSAGE_TEMPLATE, htmlspecialchars($text))))->asXML();
        $body = Utils::streamFor($message);
        $request = (new Request('POST', $uri))->withBody($body);
        $response = $this->client->send($request);
        $xml = new \SimpleXMLElement($response->getBody()->getContents());

        return Message::fromXml($xml);
    }
}

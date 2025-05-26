<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Queue;

use AzurePhp\Storage\Queue\Model\Message;
use AzurePhp\Storage\Queue\Model\MessageList;
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
    public function put(string $text, int $messagettl = -1, int $visibilitytimeout = 0): Message
    {
        $uri = $this->uri->withQuery(Query::build(['visibilitytimeout' => $visibilitytimeout, 'messagettl' => $messagettl]));
        $message = (new \SimpleXMLElement(sprintf(self::MESSAGE_TEMPLATE, $text)))->asXML();
        $body = Utils::streamFor($message);
        $request = (new Request('POST', $uri))->withBody($body);
        $response = $this->client->send($request);
        $xml = new \SimpleXMLElement($response->getBody()->getContents());
        $messageList = MessageList::fromXml($xml);

        return $messageList->messages[0];
    }

    /**
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/get-messages
     */
    public function get(int $visibilitytimeout = 30, int $numofmessages = 1): MessageList
    {
        return $this->retrieve(false, $numofmessages, $visibilitytimeout);
    }

    /**
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/peek-messages
     */
    public function peek(int $numofmessages = 1): MessageList
    {
        return $this->retrieve(true, $numofmessages);
    }

    private function retrieve(bool $peek, int $numofmessages = 1, int $visibilitytimeout = 30): MessageList
    {
        $query = ['visibilitytimeout' => $visibilitytimeout, 'numofmessages' => $numofmessages];

        if ($peek) {
            unset($query['visibilitytimeout']);
            $query['peekonly'] = 'true';
        }

        $uri = $this->uri->withQuery(Query::build($query));
        $request = new Request('GET', $uri);
        $response = $this->client->send($request);
        $xml = new \SimpleXMLElement($response->getBody()->getContents());

        return MessageList::fromXml($xml);
    }

    /**
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/delete-message2
     */
    public function delete(string $id, string $popreceipt): void
    {
        $path = rtrim($this->uri->getPath(), '/').'/'.$id;
        $query = ['popreceipt' => $popreceipt];
        $uri = $this->uri->withPath($path)->withQuery(Query::build($query));
        $request = new Request('DELETE', $uri);

        $this->client->send($request);
    }
}

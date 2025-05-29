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
    public function put(string $text, int $messageTtl = -1, int $visibilityTimeout = 0): Message
    {
        $uri = $this->uri->withQuery(Query::build(['visibilitytimeout' => $visibilityTimeout, 'messagettl' => $messageTtl]));
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
    public function get(int $visibilityTimeout = 30, int $numOfMessages = 1): MessageList
    {
        return $this->retrieve(false, $numOfMessages, $visibilityTimeout);
    }

    /**
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/peek-messages
     */
    public function peek(int $numOfMessages = 1): MessageList
    {
        return $this->retrieve(true, $numOfMessages);
    }

    private function retrieve(bool $peek, int $numOfMessages = 1, int $visibilityTimeout = 30): MessageList
    {
        $query = ['visibilitytimeout' => $visibilityTimeout, 'numofmessages' => $numOfMessages];

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
    public function delete(string $id, string $popReceipt): void
    {
        $path = rtrim($this->uri->getPath(), '/').'/'.$id;
        $query = ['popreceipt' => $popReceipt];
        $uri = $this->uri->withPath($path)->withQuery(Query::build($query));
        $request = new Request('DELETE', $uri);

        $this->client->send($request);
    }

    public function update(string $id, string $popReceipt, string $text, int $visibilityTimeout = 0): void
    {
        $path = rtrim($this->uri->getPath(), '/').'/'.$id;
        $query = ['popreceipt' => $popReceipt, 'visibilitytimeout' => $visibilityTimeout];
        $uri = $this->uri->withPath($path)->withQuery(Query::build($query));

        $message = (new \SimpleXMLElement(sprintf(self::MESSAGE_TEMPLATE, $text)))->asXML();
        $body = Utils::streamFor($message);

        $request = (new Request('PUT', $uri))->withBody($body);
        $this->client->send($request);
    }

    /**
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/clear-messages
     */
    public function clear(int $timeout = 60): void
    {
        $uri = $this->uri->withQuery(Query::build(['timeout' => $timeout]));
        $this->client->send(new Request('DELETE', $uri));
    }
}

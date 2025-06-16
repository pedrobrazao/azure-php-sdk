<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Queue;

use AzurePhp\Storage\Common\Client\AbstractClient;
use AzurePhp\Storage\Queue\Auth\SharedAccessKey;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Query;
use Psr\Http\Message\UriInterface;

final readonly class QueueClient extends AbstractClient
{
    /**
     * @param array<callable|scalar|scalar[]> $options
     */
    public static function create(string $connectionString, array $options = []): self
    {
        return self::factory($connectionString, 'queue', [], SharedAccessKey::class, $options);
    }

    /**
     * @param array<string, string> $params
     */
    private function uriForQueue(string $queueName, array $params = []): UriInterface
    {
        $path = sprintf('%s/%s', rtrim($this->uri->getPath(), '/'), trim($queueName));
        $query = Query::build([...Query::parse($this->uri->getQuery()), ...$params]);

        return $this->uri->withPath($path)->withQuery($query);
    }

    /**
     * @param array<string, scalar> $params
     */
    private function uriForMessage(string $queueName, ?string $messageId = null, array $params = []): UriInterface
    {
        $uri = $this->uriForQueue($queueName, $params);
        $path = sprintf('%s/messages%s', rtrim($uri->getPath(), '/'), null !== $messageId ? '/'.$messageId : '');

        return $this->uri->withPath($path);
    }

    /**
     * @param array<string, array<string, scalar>|callable|scalar> $options
     */
    public function queueExists(string $queueName, array $options = []): bool
    {
        $uri = $this->uriForQueue($queueName, [
            'comp' => 'metadata',
        ]);

        try {
            $response = $this->head($uri, $options);
        } catch (RequestException $e) {
            $response = $e->getResponse();

            if (null !== $response && 404 === $response->getStatusCode()) {
                return false;
            }

            throw $e;
        }

        return 200 === $response->getStatusCode();
    }

    /**
     * @param array<string, string>                   $metadata
     * @param array<string, callable|scalar|scalar[]> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/create-queue4
     */
    public function createQueue(string $queueName, array $metadata = [], array $options = []): PromiseInterface
    {
        $uri = $this->uriForQueue($queueName);

        foreach ($metadata as $name => $value) {
            $options['headers']['x-ms-meta-'.$name] = $value;
        }

        return $this->putAsync($uri, $options);
    }

    /**
     * @param array<string, callable|scalar|scalar[]> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/delete-queue
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/delete-queue3
     */
    public function deleteQueue(string $queueName, array $options = []): PromiseInterface
    {
        $uri = $this->uriForQueue($queueName);

        return $this->deleteAsync($uri, $options);
    }

    /**
     * @param array<string, array<string, scalar>|callable|scalar> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/list-queues1
     */
    public function listQueues(string $prefix = '', ?string $marker = null, int $maxResults = 5000, bool $withMetadata = false, array $options = []): PromiseInterface
    {
        $query = [
            'comp' => 'list',
            'prefix' => $prefix,
            'maxresults' => $maxResults,
        ];

        if (null !== $marker) {
            $query['marker'] = $marker;
        }

        if ($withMetadata) {
            $query['include'] = 'metadata';
        }

        $uri = $this->uri->withQuery(Query::build($query));

        return $this->getAsync($uri, $options);
    }

    /**
     * @param array<string, array<string, scalar>|callable|scalar> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/get-queue-metadata
     */
    public function getQueueMetadata(string $queueName, array $options = []): PromiseInterface
    {
        $uri = $this->uriForQueue($queueName, [
            'comp' => 'metadata',
        ]);

        return $this->getAsync($uri, $options);
    }

    /**
     * @param array<string, string>                                $metadata
     * @param array<string, array<string, scalar>|callable|scalar> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/set-queue-metadata
     */
    public function setQueueMetadata(string $queueName, array $metadata, array $options = []): PromiseInterface
    {
        $uri = $this->uriForQueue($queueName, [
            'comp' => 'metadata',
        ]);

        foreach ($metadata as $name => $value) {
            $options['headers']['x-ms-meta-'.$name] = $value;
        }

        return $this->putAsync($uri, $options);
    }

    /**
     * @param array<string, array<string, scalar>|callable|scalar> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/put-message
     */
    public function putMessage(string $queueName, string $message, int $visibilityTimeout = 0, int $messageTtl = -1, array $options = []): PromiseInterface
    {
        $uri = $this->uriForMessage($queueName, null, [
            'visibilitytimeout' => $visibilityTimeout,
            'messagettl' => $messageTtl,
        ]);

        $xml = new \SimpleXMLElement('<QueueMessage></QueueMessage>');
        $xml->addChild('MessageText', $message);
        $options['body'] = $xml->asXML();

        return $this->putAsync($uri, $options);
    }

    /**
     * @param array<string, array<string, scalar>|callable|scalar> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/get-messages
     */
    public function getMessages(string $queueName, int $numOfMessages = 1, int $visibilityTimeout = 30, array $options = []): PromiseInterface
    {
        $uri = $this->uriForMessage($queueName, null, [
            'numofmessages' => $numOfMessages,
            'visibilitytimeout' => $visibilityTimeout,
        ]);

        return $this->getAsync($uri, $options);
    }

    /**
     * @param array<string, array<string, scalar>|callable|scalar> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/peek-messages
     */
    public function peekMessages(string $queueName, int $numOfMessages = 1, array $options = []): PromiseInterface
    {
        $uri = $this->uriForMessage($queueName, null, [
            'numofmessages' => $numOfMessages,
            'peekonly' => 'true',
        ]);

        return $this->getAsync($uri, $options);
    }

    /**
     * @param array<string, array<string, scalar>|callable|scalar> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/delete-message2
     */
    public function deleteMessage(string $queueName, string $messageId, string $popReceipt, array $options = []): PromiseInterface
    {
        $uri = $this->uriForMessage($queueName, $messageId, [
            'popreceipt' => $popReceipt,
        ]);

        return $this->deleteAsync($uri, $options);
    }

    /**
     * @param array<string, array<string, scalar>|callable|scalar> $options
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/clear-messages
     */
    public function clearMessages(string $queueName, array $options = []): PromiseInterface
    {
        $uri = $this->uriForMessage($queueName);

        return $this->deleteAsync($uri, $options);
    }
}

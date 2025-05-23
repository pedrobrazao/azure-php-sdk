<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Queue\Model;

final readonly class QueueList implements \Countable
{
    /**
     * @param Queue[] $queues
     */
    public function __construct(
        public string $prefix,
        public string $marker,
        public int $maxResults,
        public array $queues,
        public string $nextMarker
    ) {}

    public function count(): int
    {
        return count($this->queues);
    }

    public static function fromXml(\SimpleXMLElement $xml): self
    {
        $queues = [];

        foreach ($xml->Queues->children() as $queue) {
            $queues[] = Queue::fromXml($queue);
        }

        return new self(
            (string) $xml->Prefix,
            (string) $xml->marker,
            (int) $xml->MaxResults,
            $queues,
            (string) $xml->NextMarker
        );
    }
}

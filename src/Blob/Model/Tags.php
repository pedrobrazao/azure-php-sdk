<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Blob\Model;

final class Tags implements \Countable
{
    public const HEADER_PREFIX = 'x-ms-meta-';

    /**
     * @param string[] $tags
     */
    public function __construct(
        private array $tags = []
    ) {}

    public function count(): int
    {
        return count($this->tags);
    }

    /**
     * @return string[]
     */
    public function toArray(): array
    {
        return $this->tags;
    }

    public function push(string $key, string $value): self
    {
        $this->tags[$key] = $value;

        return $this;
    }

    public static function fromXml(\SimpleXMLElement $xml): self
    {
        $tags = [];

        foreach ($xml->TagSet->children() as $tag) {
            $tags[(string) $tag->Key] = (string) $tag->Value;
        }

        return new self($tags);
    }

    public function toXml(): \SimpleXMLElement
    {
        $xml = new \SimpleXMLElement('<Tags></Tags>');
        $tagSet = $xml->addChild('TagSet');

        foreach ($this->tags as $key => $value) {
            $tag = $tagSet->addChild('Tag');
            $tag->addChild('Key', $key);
            $tag->addChild('Value', $value);
        }

        return $xml;
    }
}

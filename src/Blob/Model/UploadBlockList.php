<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Blob\Model;

final class UploadBlockList implements \Countable
{
    /**
     * @param UploadBlock[] $blocks
     */
    public function __construct(
        private array $blocks = []
    ) {}

    public function count(): int
    {
        return count($this->blocks);
    }

    public function push(UploadBlock $block): self
    {
        $this->blocks[] = $block;

        return $this;
    }

    public function toXml(): \SimpleXMLElement
    {
        $xml = new \SimpleXMLElement('<BlockList></BlockList>');

        foreach ($this->blocks as $block) {
            $xml->addChild($block->type, $block->getId());
        }

        return $xml;
    }
}

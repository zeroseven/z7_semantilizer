<?php

namespace Zeroseven\Semantilizer\Models;

class ContentCollection
{

    /** @var array */
    protected $elements = [];

    public function prepend(Content $content): void
    {
        array_unshift($this->elements, $content);
    }

    public function append(Content $content): void
    {
        $this->elements[$content->getUid()] = $content;
    }

    public function override(Content $content): void
    {
        if(!$this->getElement()) {
            throw new \Exception('The element cannot be overwritten because it does not exist');
        }

        $this->elements[$content->getUid()] = $content;
    }

    public function getElements(): array
    {
        return $this->elements;
    }

    public function getElement(Content $element): ?Content
    {
        return $this->getKey($element->getUid());
    }

    public function getFirstElement(): ?Content
    {
        return $this->elements[$this->getFirstKey()] ?? null;
    }

    public function getKey(int $key): ?Content
    {
        return $this->elements[$key] ?? null;
    }

    public function getFirstKey(): ?int
    {
        return $this->count() ? (int)array_key_first($this->getElements()) : null;
    }

    public function count(): int
    {
        return count($this->getElements());
    }
}

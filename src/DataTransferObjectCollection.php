<?php

declare(strict_types=1);

namespace Knobik\DataTransferObject;

use Iterator;
use Countable;
use ArrayAccess;

/**
 * Class DataTransferObjectCollection
 * @package Knobik\DataTransferObject
 */
abstract class DataTransferObjectCollection implements
    ArrayAccess,
    Iterator,
    Countable
{
    /** @var array */
    protected $collection;

    /** @var int */
    protected $position = 0;

    /**
     * DataTransferObjectCollection constructor.
     * @param array $collection
     */
    public function __construct(array $collection = [])
    {
        $this->collection = $collection;
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return $this->collection[$this->position];
    }

    /**
     * @param mixed $offset
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        return $this->collection[$offset] ?? null;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->collection[] = $value;
        } else {
            $this->collection[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->collection);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->collection[$offset]);
    }

    /**
     *
     */
    public function next()
    {
        $this->position++;
    }

    /**
     * @return int
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return array_key_exists($this->position, $this->collection);
    }

    /**
     *
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * @return array
     * @throws \ReflectionException
     */
    public function toArray(): array
    {
        $collection = $this->collection;

        foreach ($collection as $key => $item) {
            if (
                ! $item instanceof DataTransferObject
                && ! $item instanceof self
            ) {
                continue;
            }

            $collection[$key] = $item->toArray();
        }

        return $collection;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->collection);
    }
}

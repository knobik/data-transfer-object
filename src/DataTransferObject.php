<?php

declare(strict_types=1);

namespace Knobik\DataTransferObject;

use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

/**
 * Class DataTransferObject
 * @package Knobik\DataTransferObject
 */
abstract class DataTransferObject
{
    /** @var array */
    protected $exceptKeys = [];

    /** @var array */
    protected $onlyKeys = [];

    /**
     * @var bool
     */
    protected $strict = true;

    /**
     * @param array $parameters
     *
     * @return ImmutableDataTransferObject|static
     */
    public static function immutable(array $parameters): ImmutableDataTransferObject
    {
        return new ImmutableDataTransferObject(new static($parameters));
    }

    public function __construct(array $parameters)
    {
        $this->fill($parameters);
    }

    /**
     * @param array $parameters
     * @throws ReflectionException
     */
    public function fill(array $parameters)
    {
        $class = new ReflectionClass(static::class);

        $properties = $this->getPublicProperties($class);

        foreach ($properties as $property) {
            if (
                !isset($parameters[$property->getName()])
                && !$property->isDefault()
                && !$property->isNullable()
            ) {
                throw DataTransferObjectError::uninitialized($property);
            }

            $value = $parameters[$property->getName()] ?? $property->getValue($this);

            $property->set($value);

            unset($parameters[$property->getName()]);
        }

        if ($this->strict && count($parameters)) {
            throw DataTransferObjectError::unknownProperties(array_keys($parameters), $class->getName());
        }
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    public function all(): array
    {
        $data = [];

        $class = new ReflectionClass(static::class);

        $properties = $class->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $reflectionProperty) {
            $data[$reflectionProperty->getName()] = $reflectionProperty->getValue($this);
        }

        return $data;
    }

    /**
     * @param string ...$keys
     *
     * @return static
     */
    public function only(string ...$keys): DataTransferObject
    {
        $valueObject = clone $this;

        $valueObject->onlyKeys = array_merge($this->onlyKeys, $keys);

        return $valueObject;
    }

    /**
     * @param string ...$keys
     *
     * @return static
     */
    public function except(string ...$keys): DataTransferObject
    {
        $valueObject = clone $this;

        $valueObject->exceptKeys = array_merge($this->exceptKeys, $keys);

        return $valueObject;
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    public function toArray(): array
    {
        if (count($this->onlyKeys)) {
            $array = Arr::only($this->all(), $this->onlyKeys);
        } else {
            $array = Arr::except($this->all(), $this->exceptKeys);
        }

        $array = $this->parseArray($array);

        return $array;
    }

    /**
     * @param array $array
     * @return array
     * @throws ReflectionException
     */
    protected function parseArray(array $array): array
    {
        foreach ($array as $key => $value) {
            if (
                $value instanceof DataTransferObject
                || $value instanceof DataTransferObjectCollection
            ) {
                $array[$key] = $value->toArray();

                continue;
            }

            if (!is_array($value)) {
                continue;
            }

            $array[$key] = $this->parseArray($value);
        }

        return $array;
    }

    /**
     * @param ReflectionClass $class
     *
     * @return array|Property[]
     */
    protected function getPublicProperties(ReflectionClass $class): array
    {
        $properties = [];

        foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $reflectionProperty) {
            $properties[$reflectionProperty->getName()] = Property::fromReflection($this, $reflectionProperty);
        }

        return $properties;
    }
}

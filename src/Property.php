<?php

declare(strict_types=1);

namespace Knobik\DataTransferObject;

use ReflectionException;
use ReflectionProperty;

/**
 * Class Property
 * @package Knobik\DataTransferObject
 */
class Property extends ReflectionProperty
{
    /** @var array */
    protected static $typeMapping = [
        'int' => 'integer',
        'bool' => 'boolean',
        'float' => 'double',
    ];

    /** @var DataTransferObject */
    protected $valueObject;

    /** @var bool */
    protected $hasTypeDeclaration = false;

    /** @var bool */
    protected $isNullable = false;

    /** @var bool */
    protected $isInitialised = false;

    /** @var array */
    protected $types = [];

    /** @var array */
    protected $arrayTypes = [];

    /**
     * @param DataTransferObject $valueObject
     * @param ReflectionProperty $reflectionProperty
     * @return Property
     * @throws ReflectionException
     */
    public static function fromReflection(DataTransferObject $valueObject, ReflectionProperty $reflectionProperty): Property
    {
        return new self($valueObject, $reflectionProperty);
    }

    /**
     * Property constructor.
     * @param DataTransferObject $valueObject
     * @param ReflectionProperty $reflectionProperty
     * @throws ReflectionException
     */
    public function __construct(DataTransferObject $valueObject, ReflectionProperty $reflectionProperty)
    {
        parent::__construct($reflectionProperty->class, $reflectionProperty->getName());

        $this->valueObject = $valueObject;

        $this->resolveTypeDefinition();
    }

    /**
     * @param $value
     */
    public function set($value): void
    {
        if (is_array($value)) {
            $value = $this->shouldBeCastToCollection($value) ? $this->castCollection($value) : $this->cast($value);
        }

        if (!$this->isValidType($value)) {
            throw DataTransferObjectError::invalidType($this, $value);
        }

        $this->isInitialised = true;

        $this->valueObject->{$this->getName()} = $value;
    }

    /**
     * @return array
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @return string
     */
    public function getFqn(): string
    {
        return "{$this->getDeclaringClass()->getName()}::{$this->getName()}";
    }

    /**
     * @return bool
     */
    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    /**
     *
     */
    protected function resolveTypeDefinition(): void
    {
        $docComment = $this->getDocComment();

        if (!$docComment) {
            $this->isNullable = true;

            return;
        }

        preg_match('/\@var ((?:(?:[\w|\\\\])+(?:\[\])?)+)/', $docComment, $matches);

        if (!count($matches)) {
            $this->isNullable = true;

            return;
        }

        $this->hasTypeDeclaration = true;

        $varDocComment = end($matches);

        $this->types = explode('|', $varDocComment);
        $this->arrayTypes = str_replace('[]', '', $this->types);

        $this->isNullable = strpos($varDocComment, 'null') !== false;
    }

    /**
     * @param $value
     * @return bool
     */
    protected function isValidType($value): bool
    {
        if (!$this->hasTypeDeclaration) {
            return true;
        }

        if ($this->isNullable && $value === null) {
            return true;
        }

        foreach ($this->types as $currentType) {
            $isValidType = $this->assertTypeEquals($currentType, $value);

            if ($isValidType) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $value
     * @return mixed
     */
    protected function cast($value)
    {
        $castTo = null;

        foreach ($this->types as $type) {
            if (!is_subclass_of($type, DataTransferObject::class)) {
                continue;
            }

            $castTo = $type;

            break;
        }

        if (!$castTo) {
            return $value;
        }

        return new $castTo($value);
    }

    /**
     * @param array $values
     * @return array
     */
    protected function castCollection(array $values): array
    {
        $castTo = null;

        foreach ($this->arrayTypes as $type) {
            if (!is_subclass_of($type, DataTransferObject::class)) {
                continue;
            }

            $castTo = $type;

            break;
        }

        if (!$castTo) {
            return $values;
        }

        $casts = [];

        foreach ($values as $value) {
            $casts[] = new $castTo($value);
        }

        return $casts;
    }

    /**
     * @param array $values
     * @return bool
     */
    protected function shouldBeCastToCollection(array $values): bool
    {
        if (empty($values)) {
            return false;
        }

        foreach ($values as $key => $value) {
            if (is_string($key)) {
                return false;
            }

            if (!is_array($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $type
     * @param $value
     * @return bool
     */
    protected function assertTypeEquals(string $type, $value): bool
    {
        if (strpos($type, '[]') !== false) {
            return $this->isValidGenericCollection($type, $value);
        }

        if ($type === 'mixed' && $value !== null) {
            return true;
        }

        return $value instanceof $type
            || gettype($value) === (self::$typeMapping[$type] ?? $type);
    }

    /**
     * @param string $type
     * @param $collection
     * @return bool
     */
    protected function isValidGenericCollection(string $type, $collection): bool
    {
        if (!is_array($collection)) {
            return false;
        }

        $valueType = str_replace('[]', '', $type);

        foreach ($collection as $value) {
            if (!$this->assertTypeEquals($valueType, $value)) {
                return false;
            }
        }

        return true;
    }
}

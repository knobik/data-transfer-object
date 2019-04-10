<?php

declare(strict_types=1);

namespace Knobik\DataTransferObject;

use TypeError;

/**
 * Class DataTransferObjectError
 * @package Knobik\DataTransferObject
 */
class DataTransferObjectError extends TypeError
{
    /**
     * @param array $properties
     * @param string $className
     * @return DataTransferObjectError
     */
    public static function unknownProperties(array $properties, string $className): DataTransferObjectError
    {
        $propertyNames = implode('`, `', $properties);

        return new self("Public properties `{$propertyNames}` not found on {$className}");
    }

    /**
     * @param Property $property
     * @param $value
     * @return DataTransferObjectError
     */
    public static function invalidType(Property $property, $value): DataTransferObjectError
    {
        if ($value === null) {
            $value = 'null';
        }

        if (is_object($value)) {
            $value = get_class($value);
        }

        if (is_array($value)) {
            $value = 'array';
        }

        $expectedTypes = implode(', ', $property->getTypes());

        return new self("Invalid type: expected {$property->getFqn()} to be of type {$expectedTypes}, instead got value `{$value}`.");
    }

    /**
     * @param Property $property
     * @return DataTransferObjectError
     */
    public static function uninitialized(Property $property): DataTransferObjectError
    {
        return new self("Non-nullable property {$property->getFqn()} has not been initialized.");
    }

    /**
     * @param string $property
     * @return DataTransferObjectError
     */
    public static function immutable(string $property): DataTransferObjectError
    {
        return new self("Cannot change the value of property {$property} on an immutable data transfer object");
    }
}

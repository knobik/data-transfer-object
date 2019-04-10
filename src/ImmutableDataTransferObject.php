<?php

namespace Knobik\DataTransferObject;

/**
 * Class ImmutableDataTransferObject
 * @package Knobik\DataTransferObject
 */
class ImmutableDataTransferObject
{
    /** @var DataTransferObject */
    protected $dataTransferObject;

    /**
     * ImmutableDataTransferObject constructor.
     * @param DataTransferObject $dataTransferObject
     */
    public function __construct(DataTransferObject $dataTransferObject)
    {
        $this->dataTransferObject = $dataTransferObject;
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        throw DataTransferObjectError::immutable($name);
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->dataTransferObject->{$name};
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->dataTransferObject, $name], $arguments);
    }
}

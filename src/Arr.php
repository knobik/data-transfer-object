<?php

declare(strict_types=1);

namespace Knobik\DataTransferObject;

use ArrayAccess;

/**
 * Class Arr
 * @package Knobik\DataTransferObject
 */
class Arr
{
    /**
     * @param $array
     * @param $keys
     * @return array
     */
    public static function only($array, $keys): array
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }

    /**
     * @param $array
     * @param $keys
     * @return array
     */
    public static function except($array, $keys): array
    {
        return static::forget($array, $keys);
    }

    /**
     * @param $array
     * @param $keys
     * @return array
     */
    public static function forget($array, $keys): array
    {
        $keys = (array) $keys;

        if (count($keys) === 0) {
            return $array;
        }

        foreach ($keys as $key) {
            // If the exact key exists in the top-level, remove it
            if (static::exists($array, $key)) {
                unset($array[$key]);

                continue;
            }

            $parts = explode('.', $key);

            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }

            unset($array[array_shift($parts)]);
        }

        return $array;
    }

    /**
     * @param $array
     * @param $key
     * @return bool
     */
    public static function exists($array, $key): bool
    {
        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }

        return array_key_exists($key, $array);
    }
}

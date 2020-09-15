<?php
namespace Stepkim\Translate\Tools;

use ArrayAccess;
use Closure;

class Arr
{
    public static function accessible($value)
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    public static function get($array, $key, $default = null)
    {
        if (!self::accessible($array)) {
            return self::value($default);
        }

        if (is_null($key)) {
            return $array;
        }

        if (self::exists($array, $key)) {
            return $array[$key];
        }

        if (strpos($key, '.') === false) {
            return $array[$key] ?? self::value($default);
        }

        foreach (explode('.', $key) as $segment) {
            if (self::accessible($array) && self::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return self::value($default);
            }
        }

        return $array;
    }

    /**
     * Flatten a multi-dimensional associative array with dots.
     *
     * @param  array   $array
     * @param  string  $prepend
     * @return array
     */
    public static function dot($array, $prepend = '')
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $results = array_merge($results, self::dot($value, $prepend.$key.'.'));
            } else {
                $results[$prepend.$key] = $value;
            }
        }

        return $results;
    }

    public static function exists($array, $key)
    {
        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }

        return array_key_exists($key, $array);
    }

    public static function snakeCase($string, $delimiter = '_')
    {
        $replace = '$1'.$delimiter.'$2';

        return ctype_lower($string) ? $string : strtolower(preg_replace('/(.)([A-Z])/', $replace, $string));
    }

    public static function studlyCase($string)
    {
        $string = ucwords(str_replace(['-', '_'], ' ', $string));

        return str_replace(' ', '', $string);
    }

    public static function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }

    public static function head($array)
    {
        return reset($array);
    }
}
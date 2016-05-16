<?php

class Util_ParamFilter
{
    public static function getInt(array $container, $key, $default = 0)
    {
        if (!isset($container[$key])) {
            return $default;
        }

        return intval($container[$key]);
    }

    public static function getString(array $container, $key, $default = '')
    {
        if (!isset($container[$key])) {
            return $default;
        }

        return '' . trim($container[$key]);
    }

}

?>

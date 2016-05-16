<?php
class Util_String
{
    public static function safeJsonDecode($str, $default = NULL)
    {
        if ($default === NULL) {
            $default = array();
        }
        if (empty($str)) {
            return $default;
        }
        $ret = json_decode($str, 1);
        if (empty($ret)) {
            return $default;
        }
        return $ret;
    }

}

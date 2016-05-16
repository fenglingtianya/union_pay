<?php

class Helper_Array
{

    public static function combine($a, $b)
    {
        if (empty($a) || empty($b)) {
            return array();
        }
        return array_combine($a, $b);
    }

    public static function toMap($arr, $kField, $vField = null)
    {
        if (!is_array($arr) || empty($arr)) {
            return array();
        }
        $ret = array();

        if ($vField === null) {
            foreach ($arr as $v) {
                $ret[$v[$kField]] = $v;
            }
        } else {
            foreach ($arr as $v) {
                $ret[$v[$kField]] = $v[$vField];
            }
        }
        return $ret;
    }

    public static function indexArr($arr, $kField)
    {
        if (!is_array($arr) || empty($arr)) {
            return array();
        }
        $ret = array();
        foreach ($arr as $v) {
            $ret[$v[$kField]] = $v;
        }
        return $ret;
    }

    public static function getCol($arr, $vField)
    {
        if (!is_array($arr) || empty($arr)) {
            return array();
        }
        $ret = array();
        foreach ($arr as $v) {
            $ret[] = $v[$vField];
        }
        return $ret;
    }

    public static function obj2arr($object)
    {
        if (!is_object($object) && !is_array($object)) {
            return $object;
        }

        $arr = array();
        foreach ($object as $k => $v) {
            if (is_object($v)) {
                $arr[$k] = self::obj2arr($v);
            } else if (is_array($v)) {
                $arr[$k] = self::obj2arr($v);
            } else {
                $arr[$k] = $v;
            }
        }
        return $arr;
    }

    public static function filter(array $array, array $conditions)
    {
        $ret = array();
        foreach ($array as $k => $v) {
            $isOk = TRUE;
            foreach ($conditions as $_k => $_v) {
                if ($v[$_k] != $_v) {
                    $isOk = false;
                    break;
                }
            }
            if ($isOk) {
                $ret[$k] = $v;
            }
        }
        return $ret;
    }

    /**
     * 字符串转数组
     * @param string $str 待转换字符串
     * @param string $sep 记录分隔符
     * @return array 转换完的数组
     */
    public static function str2arr($str, $sep = "\n")
    {
        return array_filter(array_map('trim', explode($sep, $str)));
    }

}

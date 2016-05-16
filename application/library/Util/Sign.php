<?php

class Util_Sign
{
    public static function getServerKey($appKey, $appSecret)
    {
        if (empty($appKey) || empty($appSecret)) {
            return false;
        }
        return $appSecret;
    }

    public static function getClientKey($appKey, $appSecret)
    {
        if (empty($appKey) || empty($appSecret)) {
            return false;
        }
        return md5($appSecret . '#' . $appKey);
    }

    public static function isValid(&$array, $secret, $sign = null)
    {
        if ($sign === null && isset($array['sign'])) {
            $sign = $array['sign'];
        }
        unset($array['sign']);

        $expectedSign = self::getSign($array, $secret);
//        echo $expectedSign;
        if ($expectedSign == $sign) {
            return true;
        }

        $arrayCopy = $array;
        $trimedArray = array_map('trim', $arrayCopy);

        return self::getSign($trimedArray, $secret) == $sign;
    }

    public static function getSign(&$params, $privateKey)
    {
        foreach ($params as $k => $v) {
            if (empty($v)) {
                unset($params[$k]);
            }
        }
        ksort($params); //对参数进行排序
        $signStr = implode('#', $params);
        
        $secret = $privateKey;
        return md5($signStr . '#' . $secret); //拼装密钥
    }

    /**
     * 过滤出需要签名的字段
     * @param array $params
     * @param array $fields 需要签名的字段
     * @return array 过滤后字段列表
     */
    public static function filter($params, $fields)
    {
        $ret = array();
        foreach ($fields as $field) {
            if (isset($params[$field])) {
                $ret[$field] = $params[$field];
            }
        }
        return $ret;
    }

}

?>

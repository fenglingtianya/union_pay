<?php

class Util_Url
{

    private static $_VALID_HTTP_SCHEMES = array(
        'HTTP',
        'HTTPS',
        '',
    );

    /**
     * 是否公网可访问的地址
     * @param type $url
     */
    public static function isPublicUrl($url)
    {
        $parts = parse_url($url);
        if (empty($parts['host'])) {
            return false;
        }
        $scheme = isset($parts['scheme']) ? strtoupper($parts['scheme']) : '';
        /**
         * scheme不是http，也不是https
         */
        if (!in_array($scheme, self::$_VALID_HTTP_SCHEMES)) {
            return false;
        }

        /**
         * host为空
         */
        $host = $parts['host'];

        if ($host == 'not.a.a.a') {
            return false;
        }
        //host为ip地址，且为内网地址
        if (preg_match('/^(\d+\.){3}\d+$/', $host) && Util_Ip::isInnerIp($host)) {
            return false;
        }

        return true;
    }

}

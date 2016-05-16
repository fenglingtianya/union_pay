<?php

class Util_Ip
{

    const Ip_UNKNOWN = 'unknown';

    /**
     * 获取用户的Ip地址用于日志输出.
     *
     * 如果用户在利用HTTP_X_FORWARDED_FOR伪造Ip，把真实的和伪造的一起返回，中间通过","分隔
     *
     * @return string
     */
    static public function getIpForLog()
    {
        $ip = self::getIp();
        $ip2 = self::getIp2();
        if ($ip2 == $ip) { // 没有伪造
            $ip2 = '';
        } else { // 伪造，那么一个不漏
            $ip2 = ',' . $ip2;
        }

        return $ip . $ip2;
    }

    static public function getInnerIp()
    {
        $devices = exec("/sbin/ip addr|grep '^[0-9]'|awk '{print $2}'|sed s/://g|tr '\n' ' '");
        $device = explode(' ', $devices);
        foreach ($device as $dev) {
            if ($dev == 'lo') {
                continue;
            }
            $ip = self::getLocalIp($dev);
            if (self::isInnerIp($ip)) {
                return $ip;
            }
        }
    }

    static public function getLocalIp($interface = "eth0")
    {
        $str = exec("/sbin/ifconfig " . $interface . " | grep 'inet addr'");
        $str = explode(":", $str, 2);
        $str = explode(" ", $str[1], 2);
        return $str[0];
    }

    static public function getInnerIp2()
    {
        $devs = array('eth0', 'eth1', 'bond0', 'bond1');
        foreach ($devs as $dev) {
            $ip = self::getLocalIp($dev);
            if (self::isInnerIp($ip)) {
                return $ip;
            }
        }
        return self::Ip_UNKNOWN;
    }

    static public function getIp()
    {
        return self::_getIp();
    }

    static public function getIp2()
    {
        return self::_getIp(true);
    }

    /**
     * 判断当前浏览器用户是否在伪造Ip地址.
     *
     * 通过HTTP_X_FORWARDED_FOR这个HTTP HEADER，用户想欺骗我们他真实的Ip
     *
     * @return bool
     */
    static public function isUserForgingClientIp()
    {
        return self::_getIp(false) != self::_getIp(true);
    }

    static public function checkIp($ip, $range)
    {
        list($range, $num) = explode("/", $range, 2);
        $num = intval($num);
        $range = ip2long($range);
        $ip = ip2long($ip);

        if ($num >= 32 || $num <= 0) {
            return $range == $ip;
        } else {
            $range = $range >> (32 - $num);
            $ip = $ip >> (32 - $num);
            return $range == $ip;
        }
    }

    static public function checkIpEx($ip, $ranges)
    {
        foreach ($ranges as $range) {
            if (self::checkIp($ip, $range)) {
                return true;
            }
        }
        return false;
    }

    static public function getServerOwnIp()
    {
        if (!empty($_SERVER['SERVER_ADDR'])) {
            return $_SERVER['SERVER_ADDR'];
        }

        return '';
    }

    static private function _getIp($reverse = false)
    {
        $xip = getenv('HTTP_X_REMOTE_ADDR');
        $xrip = self::getOuterIp($xip, $reverse);
        if ($xrip != "unknown") {
            return $xrip;
        }

        $fip = getenv('HTTP_X_FORWARDED_FOR');
        $oip = self::getOuterIp($fip, $reverse);
        if ($oip != "unknown") {
            return $oip;
        }

        $rip = getenv('REMOTE_ADDR');
        return self::getOuterIp($rip, $reverse);
    }

    static private function getOuterIp($str, $reverse = false)
    {
        $ips = preg_split('/;|,|\s/', $str);
        if ($reverse) {
            $ips = array_reverse($ips);
        }

        $rip = self::Ip_UNKNOWN;
        foreach ($ips as $ip) {
            $ip = trim($ip);
            if (ip2long($ip) === false) {
                continue;
            }
            if (!self::isInnerIp($ip)) {
                return $ip;
            } else {
                $rip = $ip;
            }
        }
        return $rip;
    }

    static public function isInnerIp($ip)
    {
        if ($ip == "127.0.0.1") {
            return true;
        }
        list($i1, $i2, $i3, $i4) = explode(".", $ip, 4);
        return ($i1 == 10 || ($i1 == 172 && 16 <= $i2 && $i2 < 32) || ($i1 == 192 && $i2 == 168));
    }

    public static function getServerIpByUrl($url)
    {
        $parseUrl = parse_url($url);
        if (empty($parseUrl['host'])) {
            return '';
        }
        return gethostbyname($parseUrl['host']);
    }

}

?>
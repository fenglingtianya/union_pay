<?php

class Helper_Http
{

    const METHOD_POST = 'POST';
    const METHOD_GET = 'GET';

    private static $_followRedirect = false;

    public static function setFollowRedirect($val)
    {
        self::$_followRedirect = $val;
    }

    public static function post($url, array $data)
    {
        $curlHandle = curl_init();
        curl_setopt($curlHandle, CURLOPT_URL, $url);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array('Expect:'));
        curl_setopt($curlHandle, CURLOPT_POST, true);
        curl_setopt($curlHandle, CURLOPT_POSTFIELDS, http_build_query($data));
        $result = curl_exec($curlHandle);

        $errno = curl_errno($curlHandle);
        if ($errno) {
            //日志参数
            if (strpos($url, '?')) {
                list($_url, $_) = explode('?', $url);
            } else {
                $_url = $url;
            }
            $errMsg = curl_error($curlHandle) . '[' . curl_errno($curlHandle) . ']';
            Logger_Logger::instance()->logError(__METHOD__, 'curl_error', $errMsg . '::' . $_url);
        }
        curl_close($curlHandle);
        return $result;
    }

    private static $_needStatusCode = false;
    private static $_statusCode = '';

    public static function needStatusCode($need)
    {
        self::$_needStatusCode = $need;
    }

    public static function getStatusCode()
    {
        return self::$_statusCode;
    }

    public static function request($url, $mode, $params = '', $needHeader = false, $timeout = NULL)
    {
        //测试环境下默认超时改成1分钟
        if ($timeout === NULL) {
            $timeout = Config_Env::getInstance()->isOnline() ? 10 : 60;
        }
        $begin = microtime(true);
        $curlHandle = curl_init();
        curl_setopt($curlHandle, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);

        if (self::$_followRedirect) {
            curl_setopt($curlHandle, CURLOPT_FOLLOWLOCATION, true);
        }
        if ($needHeader) {
            curl_setopt($curlHandle, CURLOPT_HEADER, true);
        }

        //日志参数
        $logParams = $params;
        $logUrl = $url;

        if ($mode == self::METHOD_POST) {
            curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array('Expect:'));
            curl_setopt($curlHandle, CURLOPT_POST, true);
            if (is_array($params)) {
                curl_setopt($curlHandle, CURLOPT_POSTFIELDS, http_build_query($params));
            } else {
                curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $params);
            }
        } else {
            if (is_array($params)) {
                $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
            } else {
                $url .= (strpos($url, '?') === false ? '?' : '&') . $params;
            }
        }
        curl_setopt($curlHandle, CURLOPT_URL, $url);
        if (substr($url, 0, 5) == 'https') {
            curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, false);
        }

        $result = curl_exec($curlHandle);
        if (self::$_needStatusCode) {
            self::$_statusCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        }

        if ($needHeader) {
            $tmp = $result;
            $result = array();
            $info = curl_getinfo($curlHandle);
            $result['header'] = substr($tmp, 0, $info['header_size']);
            $result['body'] = trim(substr($tmp, $info['header_size']));  //直接从header之后开始截取，因为 1.body可能为空   2.下载可能不全
        }
        $errno = curl_errno($curlHandle);
        if ($errno) {
            //日志参数
            $serverIp = Util_Ip::getServerIpByUrl($url);
            $errMsg = curl_error($curlHandle) . '[' . curl_errno($curlHandle) . ']::server_ip[' . $serverIp . ']';
            if (strpos($url, '?')) {
                list($_url, $_) = explode('?', $url);
            } else {
                $_url = $url;
            }
            Logger_Logger::instance()->logError(__METHOD__, 'curl_error', $errMsg . '::' . $_url);
        }
        curl_close($curlHandle);
        return $result;
    }

    public static function getRequestMethod()
    {
        if (!array_key_exists('REQUEST_METHOD', $_SERVER)) {
            return 'HEAD';
        }
        return $_SERVER['REQUEST_METHOD'];
    }

    public static function setCookieFromHeader($header, $expire = 0, $path, $domain, $secure = false, $httponly = false)
    {
        $matches = array();
        $sum = preg_match_all('/Set-Cookie: ([^;=]+)=([^;=]+);/', $header, $matches);
        for ($i = 0; $i < $sum; $i++) {
            setcookie(urldecode($matches[1][$i]), urldecode($matches[2][$i]), $expire, $path, $domain);
        }
        return $sum;
    }

    public static function getClientIp()
    {
        $onlineIp = '';
        if (isset($_SERVER['HTTP_X_REMOTE_ADDR'])) {
            $onlineIp = $_SERVER['HTTP_X_REMOTE_ADDR'];
        } elseif (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $onlineIp = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $onlineIp = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $onlineIp = getenv('REMOTE_ADDR');
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $onlineIp = $_SERVER['REMOTE_ADDR'];
        }
        return trim($onlineIp);
    }
}

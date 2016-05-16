<?php

class Helper_HttpClient
{

    public static function get($url, $param = array(), $headers = array(), $referer = '', $agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)')
    {
        if (!empty($param)) {
            $url .= strstr('?', $url) ? '&' : '?';
            $url .= http_build_query($param);
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
        curl_setopt($curl, CURLOPT_POST, false);
        curl_setopt($curl, CURLOPT_USERAGENT, $agent);
        curl_setopt($curl, CURLOPT_REFERER, $referer);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_COOKIESESSION, true);
        curl_setopt($curl, CURLOPT_HTTPPROXYTUNNEL, false);

        $content = curl_exec($curl);
        curl_close($curl);

        return $content;
    }

    public static function post($url, $param, $referer = '', $agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)', $headers = array())
    {
        if (empty($param)) {
            return false;
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); //
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); //
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_USERAGENT, $agent);
        curl_setopt($curl, CURLOPT_REFERER, $referer);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_COOKIESESSION, true);
        curl_setopt($curl, CURLOPT_HTTPPROXYTUNNEL, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        if (is_array($param)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($param));
        } else {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $param);
        }

        $content = curl_exec($curl);
        curl_close($curl);

        return $content;
    }

    public static function postMultipart($url, $param)
    {
        list($boundary, $multipartbody) = self::build_http_query_multi($param);
        $headers[] = 'Content-Type: multipart/form-data; boundary=' . $boundary;
        return self::post($url, $multipartbody, '', '', $headers);
    }

    /**
     * 组织multipart/form-data格式的数据
     */
    public static function build_http_query_multi($params)
    {
        if (!$params) {
            return '';
        }

        uksort($params, 'strcmp');
        $pairs = array();
        $boundary = '';
        $boundary = uniqid('------------------');
        $MPboundary = '--' . $boundary;
        $endMPboundary = $MPboundary . '--';
        $multipartbody = '';

        foreach ($params as $parameter => $value) {
            if (in_array($parameter, array('pic', 'image'))) {
                $content = $value;
                $filename = Tool_Str::genPasswd(6);
                $multipartbody .= $MPboundary . "\r\n";
                $multipartbody .= 'Content-Disposition: form-data; name="' . $parameter . '"; filename="' . $filename . '"' . "\r\n";
                $multipartbody .= "Content-Type: image/unknown\r\n\r\n";
                $multipartbody .= $content . "\r\n";
            } else {
                $multipartbody .= $MPboundary . "\r\n";
                $multipartbody .= 'content-disposition: form-data; name="' . $parameter . "\"\r\n\r\n";
                $multipartbody .= $value . "\r\n";
            }
        }

        $multipartbody .= $endMPboundary;
        return array($boundary, $multipartbody);
    }

    /**
     * 发送文件到某个host
     * @param type $filePath
     * @param type $fileName
     * @param type $url
     * @param type $host
     * @return type
     */
    public static function sendFile2Host($filePath, $fileName, $url, $host)
    {
        $ch = curl_init();
        if (substr($url, 0, 5) == 'https') {
            curl_setopt($ch, CURLOPT_SSLVERSION, 3);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_USERAGENT, 'MOBILE_SDK_REPUBLIC');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (!empty($host)) {
            $parts = explode('/', $url);
            $urlParts = parse_url($url);
            if ($urlParts['port'] != 80) {
                curl_setopt($ch, CURLOPT_PORT, $urlParts['port']);
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Host: ' . $urlParts['host']));
            $parts[2] = $host;
            $url = implode('/', $parts);
        }
        $postData['name'] = $fileName;
        $postData['file'] = "@" . $filePath;
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_URL, $url);
        $ret = curl_exec($ch);
        return $ret;
    }

    public static function getHttpHost()
    {
        if (isset($_SERVER['HTTP_HOST'])) {
            return $_SERVER['HTTP_HOST'];
        }

        if (isset($_SERVER['SERVER_NAME'])) {
            if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '80') {
                return $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'];
            }
            return $_SERVER['SERVER_NAME'];
        }
        return '';
    }

}

?>

<?php

/**
 * object版Helper_Http
 */
class Helper_Curl
{

    const METHOD_POST = 'POST';
    const METHOD_GET = 'GET';

    private $_ch;
    private $_url;
    private $_host;
    private $_reqParams;
    private $_respStr;
    private $_respHeader;
    private $_respBody;
    private $_curlInfo;
    private $_curlErr;

    /**
     *
     * @var Payment_Monitor_CurlLogger
     */
    private $_monitor;

    public function __construct($url = null, $reqParams = null)
    {
        $this->_ch = curl_init();
        $this->setUrl($url);
        if ($reqParams !== null) {
            $this->setReqParams($reqParams);
        }
        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);
        $this->setFollowRedirect(true);
        curl_setopt($this->_ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->_ch, CURLOPT_HEADER, true);

        $this->setTimeout();
    }

    public function ignoreSSL()
    {
        $this->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $this->setOpt(CURLOPT_SSL_VERIFYHOST, false);
    }

    public function setUrl($url)
    {
        $this->_url = rtrim($url, '?');
        if (substr($url, 0, 5) == 'https') {
            curl_setopt($this->_ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($this->_ch, CURLOPT_SSL_VERIFYHOST, false);
        }
    }

    public function setHost($host)
    {
        $this->_host = $host;
    }

    public function setReqParams($reqParams)
    {
        if (is_array($reqParams)) {
            $this->_reqParams = $reqParams;
        } else {
            parse_str($reqParams, $this->_reqParams);
        }
        if (empty($this->_reqParams)) {
            $this->_reqParams = array();
        }
    }

    public function setTimeout($timeout = null)
    {
        //测试环境下默认超时改成1分钟
        if ($timeout === null) {
            $timeout = Config_Env::getInstance()->isOnline() ? 10 : 60;
        }
        return curl_setopt($this->_ch, CURLOPT_TIMEOUT, $timeout);
    }

    public function __destruct()
    {
        curl_close($this->_ch);
    }

    public function setFollowRedirect($val)
    {
        return curl_setopt($this->_ch, CURLOPT_FOLLOWLOCATION, !!$val);
    }

    public function setOpt($key, $val)
    {
        return curl_setopt($this->_ch, $key, $val);
    }

    public function setMonitor($monitor)
    {
        $this->_monitor = $monitor;
    }

    public function getStatusCode()
    {
        return curl_getinfo($this->_ch, CURLINFO_HTTP_CODE);
    }

    public function getRespHeader()
    {
        return $this->_respHeader;
    }

    public function getRespBody()
    {
        return $this->_respBody;
    }

    public function request($mode)
    {
        if ($mode == self::METHOD_POST) {
            return $this->post();
        } else {
            return $this->get();
        }
    }

    public function get()
    {
        if (empty($this->_reqParams)) {
            $url = $this->_url;
        } else {
            $url = $this->_url . (strpos($this->_url, '?') === false ? '?' : '&') . http_build_query($this->_reqParams);
        }
        curl_setopt($this->_ch, CURLOPT_URL, $url);
        if ($this->_host) {
            curl_setopt($this->_ch, CURLOPT_HTTPHEADER, array('Host: ' . $this->_host));
        }
        $this->_respStr = curl_exec($this->_ch);
        $this->_parseResp();
        return $this->_respBody;
    }

    public function post()
    {
        curl_setopt($this->_ch, CURLOPT_URL, $this->_url);
        curl_setopt($this->_ch, CURLOPT_HTTPHEADER, array('Expect:'));
        if ($this->_host) {
            curl_setopt($this->_ch, CURLOPT_HTTPHEADER, array('Host: ' . $this->_host));
        }
        curl_setopt($this->_ch, CURLOPT_POST, true);
        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, http_build_query($this->_reqParams));
        $this->_respStr = curl_exec($this->_ch);
        $this->_parseResp();
        return $this->_respBody;
    }

    private function _parseResp()
    {
        $errNo = curl_errno($this->_ch);
        $this->_curlErr = array(
            'errno' => $errNo,
            'error' => curl_error($this->_ch),
        );
        $info = curl_getinfo($this->_ch);
        $this->_curlInfo = $info;
        $this->_monitor && $this->_monitor->setUrl($this->_url);
        if (empty($errNo)) {
            $this->_respHeader = substr($this->_respStr, 0, $info['header_size']);
            $this->_respBody = trim(substr($this->_respStr, $info['header_size']));
            $this->_monitor && $this->_monitor->logStat($this->_curlInfo);
        } else {
            $this->_monitor && $this->_monitor->logErr($this->_curlErr);
        }
    }

    public function getInfo()
    {
        return $this->_curlInfo;
    }

    public function getError()
    {
        return array(
            'errno' => curl_errno($this->_ch),
            'error' => curl_error($this->_ch),
        );
    }

}

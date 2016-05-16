<?php

/**
 * curl性能监控和错误监控，配合Helper_Curl使用
 */
class Monitor_CurlLogger
{

    const SEP = '##';
    const EXT_SEP = '::';

    //日志标记，从哪打的点
    private $_mark;
    //请求的地址
    private $_url;
    //扩展字段，可以存入app_key,通道等信息
    private $_ext;
    //默认不记录请求参数
    private $_paramSwitch = false;

    /**
     * 是否在url中记录请求参数(从安全性考虑，可以禁止记录参数)
     * @param type $switch
     */
    public function setParamSwitch($switch = NULL)
    {
        $this->_paramSwitch = $switch;
    }

    public function setMark($mark)
    {
        $this->_mark = $mark;
    }

    public function setUrl($url)
    {
        $this->_url = $url;
    }

    private function _getUrl()
    {
        if ($this->_paramSwitch) {
            return $this->_url;
        }

        if (strpos($this->_url, '?') === false) {
            return $this->_url;
        }

        $parts = explode('?', $this->_url);
        return $parts[0];
    }

    public function setExt($ext)
    {
        if (is_array($ext)) {
            $this->_ext = join(self::EXT_SEP, $ext);
        } else {
            $this->_ext = $ext;
        }
    }

    /**
     * http慢请求监控
     * @param type $info
     */
    public function logStat($info)
    {
        $defaultArr = array(
            '_mark' => $this->_mark,
            '_ext' => $this->_ext,
            '_url' => $this->_getUrl(),
            'http_code' => '',
            'total_time' => '',
            'redirect_count' => '',
        );
        $msg = $this->_getMsg($defaultArr, $info);
        Logger_Logger::instance()->logInfo('curl_monitor', 'curl_log', $msg);
    }

    /**
     * 错误监控
     * @param type $info
     */
    public function logErr($info)
    {
        $defaultArr = array(
            '_mark' => $this->_mark,
            '_ext' => $this->_ext,
            '_url' => $this->_getUrl(),
            'errno' => '',
            'error' => '',
        );
        $msg = $this->_getMsg($defaultArr, $info);
        Logger_Logger::instance()->logError('curl_monitor', 'curl_err', $msg);
    }

    private function _getMsg($defaultArr, $logArr)
    {
        $logArr = array_merge($defaultArr, $logArr);
        $resultArr = array();
        foreach ($defaultArr as $k => $_) {
            $resultArr[] = $logArr[$k];
        }
        return join(self::SEP, $resultArr);
    }

}

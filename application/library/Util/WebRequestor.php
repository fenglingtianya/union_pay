<?php

/**
 * 封装了请求参数和回应记录的外发请求类
 */
class Util_WebRequestor
{

    /**
     * @var Payment_Util_RequestRecord
     */
    private $recorder = null;

    /**
     *
     * @var Helper_Curl
     */
    private $requestor = null;

    /**
     * curl执行超时时间
     * @var int
     */
    private $_timeout = 15;

    public function __construct($user, $orderid, $appKey, $appid = 0)
    {
        $this->recorder = Util_RequestRecord::getOutboundRequestRecord($user, $orderid, $appKey, $appid);
    }

    public function post($url, $params)
    {
        return $this->sendRequest($url, $params, 'POST');
    }

    public function get($url, $params)
    {
        return $this->sendRequest($url, $params, 'GET');
    }

    public function setRequestor($requestor)
    {
        $this->requestor = $requestor;
    }

    /**
     * 设置curl超时时间
     * @param int $timeout
     */
    public function setTimeout($timeout)
    {
        $this->_timeout = $timeout;
    }

    protected function request($url, $method, $paramStr)
    {
        if ($this->requestor) {
            $requestor = $this->requestor;
            $requestor->setTimeout($this->_timeout);
            $requestor->setUrl($url);
            $requestor->setReqParams($paramStr);
            $requestor->request($method);
            return array(
                'header' => $this->requestor->getRespHeader(),
                'body' => $this->requestor->getRespBody(),
            );
        }
        return Helper_Http::request($url, $method, $paramStr, true, $this->_timeout);
    }

    private $_logParams = NULL;

    public function setLogParams($params)
    {
        $this->_logParams = $params;
    }

    private function sendRequest($url, $params, $method)
    {
        $paramStr = (is_array($params)) ? http_build_query($params) : trim($params);
        $status = Util_RequestRecord::STATUS_INIT;
        $response = '';

        try {
            $result = $this->request($url, $method, $paramStr);
            $serverIp = Util_Ip::getServerIpByUrl($url);
            $this->recorder->setRequestInfo($url, $paramStr, $method, $serverIp, Util_RequestRecord::getReferer());

            $this->recorder->setRequestMethod($method);

            if (is_array($result)) {
                if (isset($result['body']) && !empty($result['body'])) {
                    $response = $result['body'];
                    $status = Util_RequestRecord::STATUS_OK;
                } else {
                    if (isset($result['header']) && !empty($result['header'])) {
                        $response = 'header: ' . $result['header'];
                    }
                    $this->recorder->addErrorMessage('no response');
                }
            } else {
                $response = $result;
                $status = Util_RequestRecord::STATUS_OK;
            }

            $this->recorder->setResponse($response);
            $this->recorder->setStatus($status);
        } catch (Exception $e) {
            $status = Util_RequestRecord::STATUS_ERR;
            $this->recorder->addErrorMessage($e->getMessage());
            $this->recorder->setStatus($status, 'exception:' . $e->getCode());
        }

        $this->saveRequest();

        return $response;
    }

    private function saveRequest()
    {
        try {
            if ($this->_logParams !== NULL) {
                $this->recorder->setRequestParams($this->_logParams);
            }
            $this->recorder->record();
        } catch (Exception $e) {
            $logger = Logger_Logger::instance();
            $logger->logError(__CLASS__, 'request_record', $e->getMessage());
        }
    }

}

?>
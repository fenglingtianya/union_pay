<?php

/**
 * 记录支付请求数据
 *
 */
class Util_RequestRecord
{

    const TABLE = 'pay_request';
    const INBOUND = 'inbound';
    const OUTBOUND = 'outbound';
    const NATIVE = 'native';
    const STATUS_OK = 1;
    const STATUS_ERR = 2;
    const STATUS_INIT = 0;

    static private $types = array(self::INBOUND, self::OUTBOUND, self::NATIVE);
    private $userId = '';
    private $orderId = '';
    private $appKey = '';
    private $appId = '';
    private $status = 0;
    private $resultDesc = '';
    private $clientIp = '';
    private $userAgent = '';
    private $serverIp = '';
    private $url = '';
    private $errors = array();
    private $response = '';
    private $requestParams = '';
    private $referer = '';

    public function __construct($userId)
    {
        $this->userId = trim($userId);
    }

    public function setAppInfo($appkey, $appid = '')
    {
        $this->appKey = trim($appkey);
        $this->appId = trim($appid);
    }

    public function setOrderId($orderid)
    {
        $this->orderId = trim($orderid);
    }

    public function setStatus($status, $desc = '')
    {
        $this->status = $status;
        $this->resultDesc = $desc;
    }

    public function setResponse($response)
    {
        $this->response = trim($response);
    }

    public function addErrorMessage($error)
    {
        $error = trim($error);
        if (!empty($error)) {
            $this->errors[] = $error;
        }
    }

    public function setRequestInfo($url, $params, $method, $ip = '', $referer = '')
    {
        $this->url = trim($url);
        $this->setRequestParams($params);
        $this->method = $method;
        $this->serverIp = trim($ip);
        $this->referer = trim($referer);
    }

    public function setRequestParams($params)
    {
        if (is_array($params)) {
            $this->requestParams = http_build_query($params);
        } else {
            $this->requestParams = trim($params);
        }
    }

    public function setClientInfo($ip, $agent = '')
    {
        $this->clientIp = trim($ip);
        $this->userAgent = trim($agent);
    }

    public function setType($type)
    {
        $this->type = trim($type);

        if (!$this->isValidType($this->type)) {
            $this->type = self::INBOUND;
        }
    }

    private function isValidType($type)
    {
        return in_array($type, self::$types);
    }

     /*
     * 把本次数据处理的信息记录在数据库里
     */
    public function record()
    {
        $id = 0;
        try {
            $arr = array(
                'app_key' => $this->appKey,
                'app_id' => $this->appId,
                'user_id' => $this->userId,
                'order_id' => $this->orderId,
                'client_ip' => $this->clientIp,
                'user_agent' => $this->userAgent,
                'server_ip' => $this->serverIp,
                'uri' => $this->url,
                'method' => $this->getRequestMethod(),
                'param' => $this->requestParams,
                'response' => $this->response,
                'status' => $this->status,
                'result' => $this->resultDesc,
                'error' => $this->concateErrors(),
                'type' => $this->type
            );

            $table = Config_Db_Table::getTableByOrderId(self::TABLE, $this->orderId);
            $reqDao = Helper_Factory::getPayDao($table);
            $id = $reqDao->insert($arr);
        } catch (Exception $e) {
            $this->addErrorMessage($e->getCode() . ":" . $e->getMessage());
        }
        return $id;
    }

    private function concateErrors()
    {
        return implode('.', $this->errors);
    }

    static public function getInboundRequestRecord($userId, $orderid, $appKey, $appid = 0)
    {
        $request = self::getRequestInstance($userId, $orderid, $appKey, $appid);

        $request->setType(self::INBOUND);

        $request->setClientInfo(Util_Ip::getIpForLog(), self::getUserAgent());

        $request->setRequestInfo(self::getServerUrl(), self::getRequestParams(), self::getRequestMethod(), self::getServerOwnIp(), self::getReferer());
        $request->setRequestMethod($_SERVER['REQUEST_METHOD']);
        return $request;
    }

    static public function getOutboundRequestRecord($userId, $orderid, $appKey, $appid)
    {
        $request = self::getRequestInstance($userId, $orderid, $appKey, $appid);

        $request->setType(self::OUTBOUND);

        $request->setClientInfo(self::getServerOwnIp(), '');

        return $request;
    }

    static public function getRequestInstance($userId, $orderid, $appKey, $appid)
    {
        $request = new self($userId);
        $request->setOrderId($orderid);
        $request->setAppInfo($appKey, $appid);

        return $request;
    }

    private static $_userAgent;

    public static function getUserAgent()
    {
        if (self::$_userAgent !== null) {
            return self::$_userAgent;
        }
        return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    }

    /**
     * @param String $userAgent
     */
    public static function setUserAgent($userAgent)
    {
        self::$_userAgent = $userAgent;
    }

    static function getReferer()
    {
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    }

    static function getServerUrl()
    {
        $protocol = '';
        if (!empty($_SERVER['SCHEME'])) {
            $scheme = strtolower($_SERVER['SCHEME']);
            if ($scheme == 'http' || $scheme == 'https') {
                $protocol = $scheme;
            }
        }

        if (empty($protocol)) {
            $isHttpsOff = empty($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) == "off";
            $protocol = $isHttpsOff ? 'http' : 'https';
        }

        $url = $protocol . '://' . $_SERVER['SERVER_NAME'] . $_SERVER["REQUEST_URI"]; //':'.$_SERVER["SERVER_PORT"]
        $queryStr = $_SERVER['QUERY_STRING'];
        if ($queryStr) {
            $url .= '?' . $queryStr;
        }
        return $url;
    }

    static public function getClientIp()
    {
        return Util_Ip::getIpForLog();
    }

    static public function getServerOwnIp()
    {
        return Util_Ip::getServerOwnIp();
    }

    private static $_requestMethod;

    static public function getRequestMethod()
    {
        if (empty(self::$_requestMethod)) {
            return strtolower($_SERVER['REQUEST_METHOD']);
        }

        return strtolower(self::$_requestMethod);
    }

    static public function setRequestMethod($requestMethod)
    {
        self::$_requestMethod = $requestMethod;
    }

    static public function getRequestParams()
    {
        $method = self::getRequestMethod();

        $params = '';
        switch (strtolower($method)) {
            case 'post' :
                $params = file_get_contents('php://input');
                if (empty($params)) {
                    $params = $_SERVER['QUERY_STRING'];
                }
                break;
            case 'get':
            default :
                $params = $_SERVER['QUERY_STRING'];
                if (empty($params)) {
                    $params = file_get_contents('php://input');
                }
        }
        return $params;
    }

}

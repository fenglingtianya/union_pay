<?php

class Base_Action_App extends Yaf_Action_Abstract
{

    protected $controller;
    protected $action;
    protected $module;
    protected $requestUri;
    //请求参数
    protected $requestParams;
    //返回值
    protected $data = array();
    
    public function execute()
    {
        $this->getUrlParts();
        $this->getReqParams();
        try {
            $this->getParam();
            $this->checkParam();
            $this->verify();
            $this->main();
        } catch (Exception $exc) {
            $this->data = array('code' => $exc->getCode(), 'msg' => $exc->getMessage());
            $error = get_class($exc) . ':' . $exc->getCode() . ':' . $exc->getMessage() . ':' . $this->requestUri;
            Logger_Logger::instance()->logError(__METHOD__, 'union_pay_exception', $error);
        }
        $this->output();
    }

    protected function getParam()
    {
    }

    protected function checkParam()
    {
    }

    protected function verify()
    {
    }

    //业务主逻辑
    protected function main()
    {
    }

    protected function output()
    {
        $this->renderJson();
    }

    protected function getReqParams()
    {
        $this->requestParams = $_POST + $_GET;
    }

    protected function verifyApp($appkey)
    {
        $platform = new Pay_QihooPlatformModel();
        return $platform->verifyApp($appkey);
    }

    protected function verifyClientSign($app, $params)
    {
        $secret = md5($app['appsecret'] . '#' . $app['appkey']);
        $isValid = Util_Sign::isValid($params, $secret); //校验签名
        if (!$isValid) {
            throw new Exception_BadRequest('api_parameter_invalid, bad sign:' . $app['appkey']);
        }
    }

    protected function checkToken($token)
    {
        return true;
    }

    protected function verifyAccessToken($token)
    {
        return true;
        $params = array(
            'access_token' => $token,
        );
        $response = Helper_Http::request('https://openapi.360.cn/mobile/check_token.json', 'get', $params);
        $retArr = json_decode($response, true);
        if ($retArr === null) {
            throw new Exception_BadRequest('verify token failed');
        }

        if (!empty($retArr['code'])) {
            throw new Exception_BadRequest($retArr['error_msg'], $retArr['code']);
        }

        return $retArr;
    }

    protected function getUrlParts()
    {
        $this->getRequest();
        $request = $this->getRequest();
        $this->module = $request->getModuleName();
        $this->controller = $request->getControllerName();
        $this->action = $request->getActionName();
        $this->requestUri = $request->getRequestUri();
    }

    protected function renderJson()
    {
        Yaf_Dispatcher::getInstance()->disableView();
        header('Content-Type: application/json');
        echo json_encode($this->data);
        exit;
    }

}

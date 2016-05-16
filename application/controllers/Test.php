<?php

class TestController extends Yaf_Controller_Abstract
{

    private $_baseUri = '';
    private static $APP = array(
//        'app_key' => 'app_key',
//        'app_secret' => 'app_secret',
        'app_key' => 'appkey',
        'app_secret' => 'appsecret',
    );

    private function _init()
    {
        if (!Config_Env::getInstance()->isTest()) {
            echo 'wrong env';
            exit;
        }
        $this->_baseUri = 'http://' . $_SERVER['HTTP_HOST'];
    }

    //下单接口
    public function createOrderAction()
    {
        $this->_init();

        $params = array(
            'app_key' => self::$APP['app_key'],
            'user_id' => 736746730,
            'amount' => 1000,
            'channel_type' => 'kuaifa',
            'notify_uri' => 'http://xuelong.union_pay.qihoo.net/test/receiveAgentNotify',
            'product_id' => 100000,
            'product_name' => '测试商品',
            'app_order_id' => time(),
            'app_uname' => '游戏名字',
            'app_uid' => '游戏id',
            'access_token' => md5('xxxxxxx'),
            'app_ext1' => 'app_ext1',
            'app_ext2' => 'app_ext2',
            'agent_uid' => 837763473,
        );
        $params['sign'] = $this->_getSign($params);
        $uri = $this->_baseUri . '/mobile/pay/create_order?';
        $this->_output($uri, $params);
        return false;
    }

    public function agentNotifyAction()
    {
        $this->_init();

        $params = array(
            'app_key' => self::$APP['app_key'],
            'user_id' => 736746730,
            'amount' => 1000,
            'agent_uid' => 837763473,
            'agent_source' => 'kuaifa',
            'agent_order_id' => 100000,
            'order_id' => '1605094236818325398',
            'agent_id' => time(),
        );
        $params['sign'] = $this->_getSign($params);
        $uri = $this->_baseUri . '/mobile/pay/agent_notify?';
        $this->_output($uri, $params);
        return false;
    }

    public function receiveAgentNotifyAction()
    {
        $params = $_GET;
        if (Util_Sign::isValid($params, self::$APP['app_secret'])) {
            echo 'ok';
            exit;
        }
        echo 'notok';
        exit;
    }

    private function _getSign($params)
    {
        $privateKey = Util_Sign::getClientKey(self::$APP['app_key'], self::$APP['app_secret']);
        return Util_Sign::getSign($params, $privateKey);
    }

    private function _output($uri, $params)
    {
        $uri = $uri . http_build_query($params);
        echo "<a href='$uri'>" . $uri . '</a>';
    }

}

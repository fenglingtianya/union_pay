<?php

require_once '/home/q/php/kafka_client/lib/kafka_client.php';

class App_NotifyModel
{

    const SYNC_NOTIFY = 'SYNC'; //同步
    const ASYNC_NOTIFY = 'ASYNC'; //异步
    const IS_SYNC = false; //通知类型开关
    const QUEUE_REDIS_FAST = 'pay_notify_fast';
    const QUEUE_REDIS_SLOW = 'pay_notify_slow';
    const QUEUE_QBUS = 'QUEUE_BUS';
    const QUEUE_REDIS = 'QUEUE_REDIS';
    const OK = 'ok';

    private $_queueType = self::QUEUE_REDIS;

    public function Notify($orderId)
    {
        $ret = '';
        if (self::IS_SYNC) {
            $ret = $this->syncNotify($orderId);
        } else {
            $ret = $this->asyncNotify($orderId);
        }
        return $ret;
    }

    //同步通知
    public function syncNotify($orderId)
    {
        $output = $this->_getNotifyInfo($orderId);
        $params = $output['params'];
        $url = $output['url'];
        $requestor = new Util_WebRequestor($params['user_id'], $orderId, $params['app_key']);
        $requestor->setRequestor($this->_getCurl($params['app_key']));
        $result = $requestor->get($url, $params);
        return $result;
    }

    //异步通知
    public function asyncNotify($orderId)
    {
        $output = $this->_getNotifyInfo($orderId);
        $output['run_num'] = 1; //运行次数
        $output['run_time'] = time();
        if ($this->_queueType == self::QUEUE_REDIS) {
            $ret = $this->_sendByRedis($output);
        } else {
            $ret = $this->_sendByQBus($output);
        }
        var_dump($ret);
        return $ret ? self::OK : '';
    }

    private function _sendByRedis($msg)
    {
        echo __METHOD__ . "\r\n";
        $cache = Helper_Factory::getCacheInstance();
        return $cache->lpush(self::QUEUE_REDIS_FAST, json_encode($msg));
    }

    private function _sendByQBus($msg)
    {
        $producer = Kafka_Producer::getInstance($this->_getQueueCluster());
        return $producer->send(json_encode($msg), self::QBUS_TOPIC);
    }

    private function _getQueueCluster()
    {
        return Config_Env::getInstance()->isOnline() ? 'zwt' : 'test';
    }

    private function _getCurl($appKey)
    {
        $curl = new Helper_Curl();
        $monitor = new Monitor_CurlLogger();
        $monitor->setParamSwitch(1);
        $monitor->setMark('unionpay_app_notify');
        $monitor->setExt(array(
            'app_key' => $appKey,
        ));
        $curl->setMonitor($monitor);
        return $curl;
    }

    private function _getNotifyInfo($orderId)
    {
        //需要返给应用端的参数(这些字段加入签名)
        $filters = array(
            'app_key' => '',
            'product_id' => '',
            'amount' => '',
            'app_uid' => '',
            'app_ext1' => '',
            'app_ext2' => '',
            'user_id' => '',
            'order_id' => '',
            'app_order_id' => '',
        );

        //游戏字段
        $recordInfo = $this->_getPlatformRecord($orderId);
        //获取第三方应用产生的订单id
        $recordInfo['app_order_id'] = $this->_getAppOrderId($orderId);

        $params = array_intersect_key($recordInfo, $filters);
        foreach ($params as $k => $v) {                                       //去掉空值，目的是不加入url传递
            if (empty($v)) {
                unset($params[$k]);
            }
        }

        $model = new App_VerifyModel();
        $app = $model->getApp($params['app_key']);
        $sign = Util_Sign::getSign($params, $app['appsecret']);
        $params['sign'] = $sign;

        return array(
            'params' => $params,
            'url' => $recordInfo['notify_uri'],
        );
    }

    private function _getAppOrderId($orderId)
    {
        $table = Config_Db_Table::getTableByOrderId('pay_app_map', $orderId);
        $dao = Helper_Factory::getPayDao($table);
        $appOrderId = $dao->queryOne(' where order_id=?', array($orderId), 'app_order_id');
        return $appOrderId;
    }

    private function _getPlatformRecord($orderId)
    {
        $table = Config_Db_Table::getTableByOrderId('pay_platform_record', $orderId);
        $dao = Helper_Factory::getPayDao($table);
        $fields = 'order_id, app_key, user_id, amount, notify_uri, product_id, app_uid, app_ext1, app_ext2';
        $platformRecord = $dao->queryRow(' where order_id=?', array($orderId), $fields);
        return $platformRecord;
    }

}

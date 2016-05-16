<?php

class Queue_AppNotifyModel extends Queue_BaseNotifyModel
{

    private $_isSucc;
    private $_err = array();

    protected function doJob()
    {
        $url = $this->task['url'];
        $params = $this->task['params'];

        $curl = $this->_getCurl();
        $requestor = new Util_WebRequestor($params['user_id'], $params['order_id'], $params['app_key']);
        $requestor->setTimeout(self::TIMEOUT);
        $requestor->setRequestor($curl);

        if (stripos($url, 'http') === false) {
            $url = 'http://' . $url;
        }
        $result = $requestor->get($url, $params);

        $err = array();
        $code = $curl->getStatusCode();
        if (empty($code)) {
            $err = $curl->getError();
            $err['response'] = '';
            $err['error_type'] = self::ERR_TYPE_CURL;
        } elseif ($code != '200') {
            $err = array(
                'errno' => $code,
                'error' => '对方通知地址不能正常工作',
                'error_type' => self::ERR_TYPE_HTTP,
                'response' => $result
            );
        }

        if (!empty($err)) {
            $this->_isSucc = false;
        } else {
            $this->_isSucc = $this->_isOk($result);
            if (empty($this->_isSucc)) {
                $err = array(
                    'errno' => 1,
                    'error' => '通知返回内容不正确，需要返回ok',
                    'error_type' => self::ERR_TYPE_RESP,
                    'response' => $result
                );
            }
        }

        //记录错误
        $this->_onError();

        //修改订单
        $this->_onSucc();

        return $this->_isSucc;
    }

    private function _getCurl()
    {
        $curl = new Helper_Curl();
        $monitor = new Monitor_CurlLogger();
        $monitor->setParamSwitch(1);
        $monitor->setMark('queue_app_notify');
        $monitor->setExt(array(
            'app_key' => $this->task['params']['app_key'],
        ));
        $curl->setMonitor($monitor);
        return $curl;
    }

    private function _isOk($str)
    {
        //去掉bom头
        while (substr($str, 0, 3) == "\xEF\xBB\xBF") {
            $str = substr($str, 3);
        }

        if (strtolower(trim($str)) == 'ok') {
            return true;
        }
        return false;
    }

    private function _onSucc()
    {
        if (!$this->_isSucc) {
            return;
        }
        $orderId = $this->task['params']['order_id'];
        $model = new Order_MarkModel();
        $model->markOrderNotified($orderId);
    }

    private function _onError()
    {
        if (!$this->_err) {
            return;
        }
        //记录错误日志
        $params = $this->task['params'];
        $notifyLog = array(
            'errno' => $this->_err['errno'],
            'error' => $this->_err['error'],
            'error_type' => $this->_err['error_type'],
            'response' => $this->_err['response'],
            'run_num' => $this->task['run_num'],
        );
        $table = Config_Db_Table::getTableByOrderId('pay_notify_log', $params['order_id']);
        $dao = Helper_Factory::getPayDao($table);
        $hasRecord = $dao->queryOne(' where order_id=?', array($params['order_id']), 'order_id');
        if ($hasRecord) {
            $ret = $dao->update($notifyLog, 'where order_id=?', array($params['order_id']));
        } else {
            $notifyLog['order_id'] = $params['order_id'];
            $notifyLog['ctime'] = date('Y-m-d H:i:s');
            $ret = $dao->insert($notifyLog);
        }
        return $ret;
    }

}

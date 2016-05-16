<?php

class Agent_NotifyModel
{

    const SUCC_MARK = 'ok';

    private $_orderId;
    private $_order = array();
    private $_payDao;
    private $_requestRecord;
    private $_response;
    private $_orderMark;

    /**
     *
     * @var Vo_AgentNotifyModel 
     */
    private $_request;

    /**
     *
     * @param Vo_AgentNotifyModel $request
     */
    public function __construct($request)
    {
        $this->_request = $request;
        $this->_orderId = $request->orderId;
        $table = Config_Db_Table::getTableByOrderId('pay_order', $this->_orderId);
        $this->_payDao = Helper_Factory::getPayDao($table);
        $this->_requestRecord = Util_RequestRecord::getInboundRequestRecord($request->userId, $this->_orderId, $request->appKey, '');
        $this->_orderMark = new Order_MarkModel();
    }

    public function notify()
    {
        $this->_beginNotify();

        $this->_verify();

        $this->_notifySucc();
        if (empty($this->order['is_success'])) {
        }

        $this->_endNotify();

        return $this->_response;
    }

    private function _beginNotify()
    {
        $order = $this->_payDao->queryRow('where id=? ', array($this->_orderId));
        if (empty($order)) {
            $error = 'order_not_found::' . $this->_orderId;
            Logger_Logger::instance()->logError(__METHOD__, 'agent_notify_invalid_order', $error);
            echo $error;
            exit;
        }

        $request = $this->_request;
        $requestRecord = $this->_requestRecord;
        if ($order['amount'] != $request->amount) {
            $agentOrderId = $request->agentOrderId;
            $notifyAmount = $request->amount;
            $_msg = "order_id={$this->_orderId}&amount={$order['amount']}&agent_order_id=$agentOrderId&notify_amount=$notifyAmount";
            $this->_logErrorAmount($_msg);
            $requestRecord->setResponse('invalid order');
            $requestRecord->addErrorMessage($_msg);
            $requestRecord->record();
            echo 'invalid order';
            exit;
        }

        $this->_order = $order;
    }

    private function _verify()
    {
        $request = $this->_request;
        $requestRecord = $this->_requestRecord;
        $error = '';

        $orderMap = $this->_getOrderMap();

        if (empty($orderMap)) {
            $error = 'order_not_found';
        }elseif ($orderMap['user_id'] != $request->userId) {
            $error = "user_id not match::user_id={$orderMap['user_id']}&notify_userid={$request->userId}";
        } else if ($orderMap['app_key'] != $request->appKey) {
            $error = "app_key not match::app_key={$orderMap['app_key']}&notify_appkey={$request->appKey}";
        } elseif ($orderMap['agent_uid'] != $request->agentUid) {
            $error = "agent_uid not match::agent_uid={$orderMap['agent_uid']}&notify_agentuid={$request->agentUid}";
        } elseif ($orderMap['agent_source'] != $request->agentSource) {
            $error = "agent_source not match::agent_source={$orderMap['agent_source']}&notify_agentsouce={$request->agentSource}";
        }

        if ($error) {
            Logger_Logger::instance()->logError(__METHOD__, 'agent_notify_fail', $this->_orderId . ' ' . $error);
            $requestRecord->setResponse('invalid order');
            $requestRecord->addErrorMessage($error);
            $requestRecord->record();
            echo 'invalid order';
            exit;
        }
    }

    private function _getOrderMap()
    {
        $table = Config_Db_Table::getTableByOrderId('pay_agent_map', $this->_orderId);
        $dao = Helper_Factory::getPayDao($table);
        $orderMap = $dao->queryRow('where order_id=?', array($this->_orderId));
        return $orderMap ? $orderMap : array();
    }

    private function _notifySucc()
    {
        echo __METHOD__ . "\r\n";
        $orderId = $this->_orderId;
        $requestRecord = $this->_requestRecord;

        $markSuccess = $this->_orderMark->markOrderSuccess($orderId);

        if (!$markSuccess) {
            $msg = $orderId . ' mark failed';
            Logger_Logger::instance()->logInfo(__METHOD__, 'error', $msg);
            $requestRecord->addErrorMessage($msg);
            $response = "mark failed";
            $requestRecord->setResponse($response);
            $requestRecord->setStatus(Util_RequestRecord::STATUS_ERR);
            $requestRecord->record();

            //第三方通知成功,但标记失败
            $this->_response = $msg;
            return;
        }

        $this->_response = $this->_notifyApp();
    }

    private function _endNotify()
    {
        $response = $this->_response;
        $requestRecord = $this->_requestRecord;
        $requestRecord->setResponse($response);
        $requestRecord->record();
    }

    private function _notifyApp()
    {
        echo __METHOD__ . "\r\n";
        $orderId = $this->_orderId;
        $requestRecord = $this->_requestRecord;
        $notifyModel = new App_NotifyModel();
        $inQueue = $notifyModel->asyncNotify($orderId);
        exit;

        if ($this->_order['notify_time'] > Order_MarkModel::DEFAULT_TIME) {
            $notifyRet = 'ok';
        } else {
            //通知应用，如果应用返回ok，则认为通知应用成功
            $notifyRet = $notifyModel->Notify($orderId);
        }

        if (strtolower(trim($notifyRet)) == 'ok') {
            $response = self::SUCC_MARK; //输出成功标识给渠道，标识通知成功。
            $this->_orderMark->markOrderNotified($orderId);
            $requestRecord->setStatus(Util_RequestRecord::STATUS_OK);
        } else {
            $msg = $orderId . ' notify failed!(' . $notifyRet . ')';
            //加入异步通知队列
            $inQueue = $notifyModel->asyncNotify($orderId);
            $response = $inQueue ? self::SUCC_MARK : 'notify failed';

            Logger_Logger::instance()->logInfo(__METHOD__, 'notice', $msg . "::" . $notifyRet);
            $requestRecord->addErrorMessage($msg);
            $requestRecord->setStatus(Util_RequestRecord::STATUS_ERR);
        }

        return $response;
    }

    //渠道通知的金额和数据库中金额不一致
    private function _logErrorAmount($msg)
    {
        Logger_Logger::instance()->logAlert(__METHOD__, 'notify_hacked', $msg);
    }

}

<?php

class Pay_AgentNotifyModel
{

    const SUCC_MARK = 'ok';

    private $_orderId;
    private $_order = array();
    private $_payDao;
    private $_requestRecord;

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
        $this->_requestRecord = Payment_Util_RequestRecord::getInboundRequestRecord($request->userId, $this->_orderId, $request->appKey, '');
    }

    public function notify()
    {
        $this->_beginNotify();

        $this->_verify();

        $this->_notifySucc();
        if (empty($this->order['is_success'])) {
            $this->_statSucc();
            $this->_addBonus();
        }

        $this->_endNotify();

        return $this->response;
    }

    private function _beforeNotify()
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
            $_msg = "order_id={$this->orderId}&amount={$order['amount']}&agent_order_id=$agentOrderId&notify_amount=$notifyAmount";
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
        } elseif ($orderMap['agent_order_id'] != $request->agentOrderId) {
            $error = "agent_order_id not match::agent_order_id={$orderMap['agent_order_id']}&notify_agentorderid={$request->agentOrderId}";
        } elseif ($orderMap['user_id'] != $request->userId) {
            $error = "user_id not match::user_id={$orderMap['user_id']}&notify_userid={$request->userId}";
        } else if ($orderMap['app_key'] != $request->appKey) {
            $error = "app_key not match::app_key={$orderMap['app_key']}&notify_appkey={$request->appKey}";
        } elseif ($orderMap['agent_uid'] != $request->agentUid) {
            $error = "agent_uid not match::agent_uid={$orderMap['agent_uid']}&notify_agentuid={$request->agentUid}";
        } elseif ($orderMap['agent_source'] != $request->agentSource) {
            $error = "agent_source not match::agent_source={$orderMap['agent_source']}&notify_agentsouce={$request->agentSource}";
        }

        if ($error) {
            Logger_Logger::instance()->logError(__METHOD__, 'agent_notify_fail', $this->orderId . ' ' . $error);
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
        
    }

    private function _notifyFail()
    {
        
    }

    private function _endNotify()
    {
        
    }

    private function _notifyApp($orderId)
    {
        
    }

    //渠道通知的金额和数据库中金额不一致
    private function _logErrorAmount($msg)
    {
        Logger_Logger::instance()->logAlert(__METHOD__, 'notify_hacked', $msg);
//        Payment_Util_Log::getInstance()->error('通知异常的订单', $msg);
    }

}

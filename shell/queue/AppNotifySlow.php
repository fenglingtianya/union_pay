<?php

/**
 * 慢通知队列，如果通知次数超过阈值，存入数据库，手动补单
 */
class Shell_Queue_AppNotifySlow
{

    private $_task = array();
    private $_queue;

    public function __construct()
    {
        $this->_queue = Helper_Factory::getCacheInstance();
    }

    public function run()
    {
//        $task = $cache->rpop(App_NotifyModel::QUEUE_TOPIC);
        $task = $this->_queue->lrange(App_NotifyModel::QUEUE_REDIS_SLOW, 0, 0);
        $this->_task = json_decode($task[0], true);
        $model = new Queue_AppNotifyModel();
        $ret = $model->processTask($this->_task);
        if ($ret == Queue_BaseNotifyModel::RET_SUCC) {
            //通知成功
            $this->_onSucc();
        } elseif ($ret == Queue_BaseNotifyModel::RET_FAIL) {
            //通知失败，保存到数据库，后续手动补单
            $this->_onFail();
        } else {
            //入队，重新通知
            $this->_onContinue();
        }
    }

    private function _onSucc()
    {
        $orderId = $this->_task['params']['order_id'];
        $updateArr = array(
            'is_notified' => 1,
        );
        $table = Config_Db_Table::getTableByOrderId('pay_fail_req', $orderId);
        $dao = Helper_Factory::getPayDao($table);
        $hasRecord = $dao->queryOne(' where order_id=?', array($orderId), 'order_id');
        if ($hasRecord) {
            $ret = $dao->update($updateArr, 'where order_id=? and is_notified=?', array($orderId, 0));
        } else {
            $ret = true;
        }
        return $ret;
    }

    private function _onFail()
    {
        $url = $this->_task['url'];
        $params = $this->_task['params'];
        $failLog = array(
            'app_key' => $params['app_key'],
            'run_num' => $this->_task['run_num'],
            'url' => $url,
            'query_str' => http_build_query($params),
        );
        $table = Config_Db_Table::getTableByOrderId('pay_fail_req', $params['order_id']);
        $dao = Helper_Factory::getPayDao($table);
        $hasRecord = $dao->queryOne(' where order_id=?', array($params['order_id']), 'order_id');
        if ($hasRecord) {
            $ret = $dao->update($failLog, 'where order_id=? and is_notified=?', array($params['order_id'], 0));
        } else {
            $failLog['order_id'] = $params['order_id'];
            $ret = $dao->insert($failLog);
        }
        return $ret;
    }

    private function _onContinue()
    {
        $this->_task['run_num'] ++;
        $res = $this->_queue->lpush(App_NotifyModel::QUEUE_REDIS_SLOW, json_encode($this->_task));
        if (!$res) {
            //入队失败，发报警邮件
        }
    }

}

require_once dirname(dirname(__FILE__)) . '/index.php';
$notifyModel = new Shell_Queue_AppNotifySlow();
$notifyModel->run();

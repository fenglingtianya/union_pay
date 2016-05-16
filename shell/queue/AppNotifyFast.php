<?php

/**
 * 快通知队列，每个订单只通知一次，如果第一次通知失败，则加入慢通知队列，避免影响后续的通知
 */
class Shell_Queue_AppNotifyFast
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
        $task = $this->_queue->lrange(App_NotifyModel::QUEUE_REDIS_FAST, 0, 0);
        $this->_task = json_decode($task[0], true);
        $model = new Queue_AppNotifyModel();
        $ret = $model->processTask($this->_task);
        if ($ret == Queue_BaseNotifyModel::RET_SUCC) {
            //通知成功
            $this->_onSucc();
        } else {
            //第一次通知失败，加入慢通知队列，重新通知
            $this->_onRepeat();
        }
    }

    private function _onSucc()
    {
        echo 'notify succ' . __METHOD__ . "\n";
    }

    private function _onRepeat()
    {
        $res = $this->_queue->lpush(App_NotifyModel::QUEUE_REDIS_SLOW, json_encode($this->_task));
        if (!$res) {
            //入队失败，发报警邮件
            echo 'in queue fail ' . __METHOD__ . "\n";
            exit;
        }
        echo 'in queue succ ' . __METHOD__ . "\n";
    }

}

require_once dirname(dirname(__FILE__)) . '/index.php';
$notifyModel = new Shell_Queue_AppNotifyFast();
$notifyModel->run();

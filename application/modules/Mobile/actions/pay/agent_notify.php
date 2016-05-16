<?php

class Agent_notifyAction extends Base_Action_App
{

    /**
     *
     * @var Vo_AgentNotifyModel
     */
    protected $_request;

    protected function getParam()
    {
        $this->_request = new Vo_AgentNotifyModel($this->requestParams);
    }

    protected function checkParam()
    {
        $needFields = array(
            'app_key',
            'amount',
            'user_id',
            'agent_uid',
            'sign',
            'agent_source',
            'agent_order_id',
            'order_id',
            'agent_id',
        );
        foreach ($needFields as $field) {
            if (empty($this->requestParams[$field])) {
                throw new Exception_BadRequest('no ' . $field);
            }
        }
    }

    protected function verify()
    {
        return;
    }

    protected function main()
    {
        $model = new Agent_NotifyModel($this->_request);
        $notifyRet = $model->notify();
        echo $notifyRet;
        exit;
    }

}

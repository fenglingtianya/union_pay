<?php

class Vo_AgentNotifyModel
{
    public $appKey;
    public $userId;
    public $amount;
    public $agentUid;
    public $agentSource;
    public $agentId;
    public $agentOrderId;
    public $orderId;
    public $sign;

    public function __construct($params)
    {
        $this->appKey = Util_ParamFilter::getString($params, 'app_key');
        $this->userId = Util_ParamFilter::getInt($params, 'user_id');
        $this->amount = Util_ParamFilter::getString($params, 'amount');
        $this->agentUid = Util_ParamFilter::getString($params, 'agent_uid');
        $this->agentSource = Util_ParamFilter::getString($params, 'agent_source');
        $this->agentId = Util_ParamFilter::getString($params, 'agent_id');
        $this->agentOrderId = Util_ParamFilter::getString($params, 'agent_order_id');
        $this->orderId = Util_ParamFilter::getInt($params, 'order_id');
        $this->sign = Util_ParamFilter::getString($params, 'sign');
    }

}

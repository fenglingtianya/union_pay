<?php

class Vo_PayRequestModel
{
    public $appKey;
    public $userId;
    public $channelType;
    public $accessToken;
    public $productId;
    public $productName;
    public $productNum;
    public $productPrice;
    public $amount;

    public $appOrderId;
    public $notifyUri;
    public $appUname;
    public $appUid;
    public $appExt1;
    public $appExt2;

    public $agentUid;
    public $sign;

    public $orderId;

    public function __construct($params)
    {
        $this->appKey = Util_ParamFilter::getString($params, 'app_key');
        $this->userId = Util_ParamFilter::getInt($params, 'user_id');
        $this->productId = Util_ParamFilter::getString($params, 'product_id');
        $this->productName = Util_ParamFilter::getString($params, 'product_name');
        $this->productNum = Util_ParamFilter::getInt($params, 'product_num');
        $this->productPrice = Util_ParamFilter::getInt($params, 'product_price');
        $this->amount = Util_ParamFilter::getString($params, 'amount');
        $this->accessToken = Util_ParamFilter::getString($params, 'access_token');
        $this->notifyUri = Util_ParamFilter::getString($params, 'notify_uri');
        $this->appOrderId = Util_ParamFilter::getString($params, 'app_order_id');
        $this->channelType = Util_ParamFilter::getString($params, 'channel_type');
        $this->appUname = Util_ParamFilter::getString($params, 'app_uname');
        $this->appUid = Util_ParamFilter::getString($params, 'app_uid');
        $this->appExt1 = Util_ParamFilter::getString($params, 'app_ext1');
        $this->appExt2 = Util_ParamFilter::getString($params, 'app_ext2');
        $this->agentUid = Util_ParamFilter::getString($params, 'agent_uid');
        $this->sign = Util_ParamFilter::getString($params, 'sign');
    }
}

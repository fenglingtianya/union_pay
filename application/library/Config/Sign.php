<?php

class Config_Sign
{

    /**
     *
     * @staticvar null $instance
     * @return Payment_Config_Sign
     */
    public static function getInstance()
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new self;
        }
        return $instance;
    }

    //支付接口参与签名的字段
    public function getPayFields()
    {
        return array(
            'app_key',
            'notify_uri',
            'product_id',
            'product_name',
            'product_num',
            'product_price',
            'amount',
            'app_uname',
            'app_uid',
            'app_ext1',
            'app_ext2',
            'app_order_id',
            'agent_uid',
            'user_id',
            'access_token',
            'channel_type',
        );
    }
}

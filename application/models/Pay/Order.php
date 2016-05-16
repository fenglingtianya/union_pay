<?php

class Pay_OrderModel
{

    /**
     *
     * @var Vo_PayRequestModel 
     */
    private $_request;

    public static function generateOrderId()
    {
        $timestamp = time();
        $timeOfDayBegin = strtotime(date('Y-m-d 00:00:00'));
        $secsInDay = str_pad($timestamp - $timeOfDayBegin, 5, '0', STR_PAD_LEFT);
        $order_sn = date('ymd') . $secsInDay . substr(microtime(), 2, 4);
        $ra = rand(100, 999);
        $orderPostfix = '8';
        return $order_sn . $ra . $orderPostfix;
    }

    /**
     *
     * @param Vo_PayRequestModel $request
     */
    public function saveOrder($request)
    {
        $this->_request = $request;
        if (!$this->_saveBaseOrder()) {
            return false;
        }

        if (!$this->_saveAppOrder()) {
            return false;
        }

        if (!$this->_savePlatformRecord()) {
            return false;
        }

        if (!$this->_saveChannelInfo()) {
            return false;
        }

        return true;
    }

    //订单基本信息
    private function _saveBaseOrder()
    {
        $request = $this->_request;
        $order = array(
            'id' => $request->orderId,
            'app_key' => $request->appKey,
            'user_id' => $request->userId,
            'amount' => $request->amount,
            'channel_type' => $request->channelType,
            'notify_uri' => $request->notifyUri,
            'notify_time' => 0,
            'success_time' => 0,
            'product_id' => $request->productId,
            'product_name' => $request->productName,
            'product_num' => $request->productNum,
            'product_price' => $request->productPrice,
        );

        $table = Config_Db_Table::getTableByOrderId('pay_order', $request->orderId);
        $isSucc = Helper_Factory::getPayDao($table)->insert($order);

        if (!$isSucc) {
            Logger_Logger::instance()->logError(__METHOD__, 'save_order_failed', var_export($order, 1));
        }
        return $isSucc;
    }

    //apporder信息
    private function _saveAppOrder()
    {
        $request = $this->_request;
        $appOrderMap = array(
            'order_id' => $request->orderId,
            'app_key' => $request->appKey,
            'app_order_id' => $request->appOrderId,
        );

        $table = Config_Db_Table::getTableByOrderId('pay_app_map', $request->orderId);
        $isSucc = Helper_Factory::getPayDao($table)->insert($appOrderMap);

        if (!$isSucc) {
            Logger_Logger::instance()->logError(__METHOD__, 'save_apporder_failed', var_export($appOrderMap, 1));
        }
        return $isSucc;
    }

    //平台请求记录
    private function _savePlatformRecord()
    {
        $request = $this->_request;
        $record = array(
            'order_id' => $request->orderId,
            'app_key' => $request->appKey,
//            'app_name' => $this->app['appname'],
            'app_name' => 'appname',
            'notify_uri' => $request->notifyUri,
            'product_id' => $request->productId,
            'product_name' => $request->productName,
            'amount' => $request->amount,
            'app_uname' => $request->appUname,
            'app_uid' => $request->appUid,
            'app_ext1' => $request->appExt1,
            'app_ext2' => $request->appExt2,
            'user_id' => $request->userId,
            'sign' => $request->sign,
        );

        $table = Config_Db_Table::getTableByOrderId('pay_platform_record', $request->orderId);
        $isSucc = Helper_Factory::getPayDao($table)->insert($record);

        if (!$isSucc) {
            Logger_Logger::instance()->logError(__METHOD__, 'save_apporder_failed', var_export($record, 1));
        }
        return $isSucc;
    }

    //渠道信息
    private function _saveChannelInfo()
    {
        $request = $this->_request;
        $channelInfo = array(
            'order_id' => $request->orderId,
            'app_key' => $request->appKey,
            'user_id' => $request->userId,
            'agent_uid' => $request->agentUid,
            'agent_source' => $request->channelType,
        );

        $table = Config_Db_Table::getTableByOrderId('pay_agent_map', $request->orderId);
        $isSucc = Helper_Factory::getPayDao($table)->insert($channelInfo);

        if (!$isSucc) {
            Logger_Logger::instance()->logError(__METHOD__, 'save_channelinfo_failed', var_export($channelInfo, 1));
        }
        return $isSucc;
    }


}

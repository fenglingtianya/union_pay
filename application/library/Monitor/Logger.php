<?php

/**
 * 监控打点
 */
class Monitor_Logger
{

    private static $_appKey = '';
    private static $_userId = '';
    private static $_bankCode = '';
    private static $_orderId = '';

    const SEP = '##';

    /**
     *
     * @return Monitor_Logger
     */
    public static function getInstance()
    {
        static $instance = NULL;
        if (!$instance) {
            $instance = new self;
        }

        return $instance;
    }

    public function setAppKey($appKey)
    {
        self::$_appKey = $appKey;
    }

    public function setUserId($userId)
    {
        self::$_userId = $userId;
    }

    public function setBankCode($bankCode)
    {
        self::$_bankCode = $bankCode;
    }

    public function setOrderId($orderId)
    {
        self::$_orderId = $orderId;
    }

    
    public function initByOrderId($orderId)
    {
        $table = Config_Db_Table::getTableByOrderId('pay_order', $orderId);
        $order = Helper_Factory::getPayDao($table)->queryRow("where id=?", array($orderId));
        if (empty($order)) {
            return;
        }
        $this->setAppKey($order['app_key']);
        $this->setUserId($order['user_id']);
        $this->setBankCode($order['pay_type']);
        $this->setOrderId($orderId);
    }

    /**
     * 创建订单
     */
    public function logCreateOd($logArr)
    {
        if (empty($logArr['code'])) {
            return;
        }
        $this->_logOrder('moni_create_od', $logArr);
    }


    /**
     * 渠道订单通知
     * @param type $logArr
     */
    public function logPcNotify($logArr)
    {
        if (empty($logArr['code'])) {
            return;
        }
        $this->_logOrder('moni_pc_notify', $logArr);
    }

    /**
     * 游戏订单通知
     */
    public function logAppNotify($logArr)
    {
        if (empty($logArr['code'])) {
            return;
        }
        $this->_logOrder('moni_app_notify', $logArr);
    }

    private function _logOrder($type, $logArr)
    {
        static $now = NULL;
        $sdkVer = NULL;
        if ($now === NULL) {
            $now = date('Y-m-d H:i:s');
            list($sdkVer, $_) = Payment_Util_UaParser::getFullVersionAndSource();
        }
        $defaultPos = array(
            'sdk_ver' => $sdkVer,
            'time' => $now,
            'app_key' => self::$_appKey,
            'bank_code' => self::$_bankCode,
            'user_id' => self::$_userId,
            'order_id' => self::$_orderId,
            'code' => '',
            'msg' => '',
        );
        $this->_log($type, $defaultPos, $logArr);
    }

    private function _log($type, $defaultArr, $logArr)
    {
        $logArr = array_merge($defaultArr, $logArr);
        $resultArr = array();
        foreach ($defaultArr as $k => $_) {
            $resultArr[] = $logArr[$k];
        }
        Logger_Logger::instance()->logStat('sdk_monitor', $type, join(self::SEP, $resultArr));
    }

}

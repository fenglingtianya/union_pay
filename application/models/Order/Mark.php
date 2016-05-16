<?php

class Order_MarkModel
{

    const DEFAULT_TIME = '0000-00-00 00:00:00';

    //标记订单成功
    public function markOrderSuccess($orderId)
    {
        $table = Config_Db_Table::getTableByOrderId('pay_order', $orderId);
        $dao = Helper_Factory::getPayDao($table);
        $successTime = $dao->queryOne(' where id=?', array($orderId), 'success_time');
        if ($successTime > self::DEFAULT_TIME) {
            return true;
        }

        $arr = array(
            'success_time' => date('Y-m-d H:i:s'),
        );
        $ret = $dao->update($arr, ' where id=?', array($orderId));
        return $ret ? true : false;
    }

    //标记订单已成功通知
    public function markOrderNotified($orderId)
    {
        $table = Config_Db_Table::getTableByOrderId('pay_order', $orderId);
        $dao = Helper_Factory::getPayDao($table);
        $notifyTime = $dao->queryOne(' where id=?', array($orderId), 'notify_time');
        if ($notifyTime > self::DEFAULT_TIME) {
            return true;
        }

        $arr = array(
            'notify_time' => date('Y-m-d H:i:s'),
        );
        $ret = $dao->update($arr, ' where id=?', array($orderId));
        return $ret ? true : false;
    }

}

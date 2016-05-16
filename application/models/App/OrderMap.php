<?php

class App_OrderMapModel
{
    public function getAppOrderId($orderId)
    {
        $table = Config_Db_Table::getTableByOrderId('pay_app_map', $orderId);
        $dao = Helper_Factory::getPayDao($table);
        $appOrderMap = $dao->queryRow(' where order_id=?', array($orderId));
        return $appOrderMap;
    }

}

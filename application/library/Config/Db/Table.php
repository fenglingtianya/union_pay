<?php

class Config_Db_Table
{
    public static function getTableByOrderId($baseTable, $orderId)
    {
        $postfix = substr($orderId, 0, 4);
        return $baseTable . '_' . $postfix;
    }

}

<?php

class Mobile_ConfigModel
{

    private $_payDao;

    public function __construct()
    {
        $this->_payDao = Helper_Factory::getPayDao('pay_mobile_config');
    }

    public function getConfig()
    {
        return $this->_payDao->queryAll($moreSql, $params, $fields);
    }

}

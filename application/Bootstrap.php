<?php

class Bootstrap extends Yaf_Bootstrap_Abstract{

    public function _initConfig()
    {
        //注入数据库配置
        $config = Yaf_Application::app()->getConfig();
        Helper_Factory::$pay = $config->get('db.pay')->toArray();
        Helper_Factory::$cache = $config->get('redis')->toArray();
    }

    public function _initPlugin(Yaf_Dispatcher $dispatcher) {
    }

	public function _initRoute(Yaf_Dispatcher $dispatcher) {
	}

    public function _initView(Yaf_Dispatcher $dispatcher){
	}
}

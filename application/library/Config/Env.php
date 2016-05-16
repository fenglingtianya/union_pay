<?php

class Config_Env
{
    //测试环境
    const TEST = 'test';
    //beta环境
    const BETA = 'beta';
    //线上环境
    const ONLINE = 'product';

    private $_yafEnv;

    private function __construct()
    {
        $this->_yafEnv = ini_get('yaf.environ');
    }

    public static function getInstance()
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new self;
        }
        return $instance;
    }

    public function isTest()
    {
        return (self::TEST == $this->_yafEnv) ? true : false;
    }

    public function isBeta()
    {
        return (self::BETA == $this->_yafEnv) ? true : false;
    }

    public function isOnline()
    {
         return (self::ONLINE == $this->_yafEnv) ? true : false;
    }

}

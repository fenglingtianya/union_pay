<?php

class Db_CacheRedis
{

    private static $_instance;
    private $_usage = 'redis';
    private $_connect = null;
    private $_redis = null;

    private function __construct($usage = 'redis')
    {
        $this->_usage = $usage;
        $this->_redis = new Redis();
    }

    public static function getInstance($usage = 'redis')
    {
        if (!isset(self::$_instance[$usage])) {
            $c = __CLASS__;
            self::$_instance[$usage] = new $c($usage);
        }

        return self::$_instance[$usage];
    }

    public function connect($host, $port, $timeout)
    {
        try {
            for ($i = 0; $i < 2; $i++) {
                $this->_connect = $this->_redis->connect($host, $port, $timeout);
                if ($this->_connect) {
                    break;
                }
                usleep(500000);
            }
        } catch (Exception $e) {
            Logger_Logger::instance()->logAlert(__METHOD__, 'redis_disconnect', "Redis@{$host}:{$port} " . $e->getMessage());
        }

        if (!$this->_connect) {
            Logger_Logger::instance()->logAlert(__METHOD__, 'redis_disconnect', "Redis@{$host}:{$port} connect timeout!!");
        }
    }

    public function __call($method, $args)
    {
        $data = null;
        try {
            if ($this->_connect && method_exists($this->_redis, $method)) {
                $data = call_user_func_array(array($this->_redis, $method), $args);
            }
        } catch (Exception $e) {
            Logger_Logger::instance()->logAlert(__METHOD__, 'redis_went_away', $e->getMessage());
        }
        return $data;
    }

}

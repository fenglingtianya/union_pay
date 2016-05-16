<?php

class Helper_Factory
{

    public static $cache = array();
    public static $pay = array();

    /**
     *
     * @return Cache_Redis
     */
    public static function getCacheInstance()
    {
        return self::_getCacheInstance(self::$cache);
    }

    /**
     *
     * @staticvar array $instances
     * @param type $conf
     * @return Cache_Redis
     */
    private static function _getCacheInstance($conf)
    {
        static $instances = array();
        $key = $conf['host'] . ':' . $conf['port'];
        if (!isset($instances[$key])) {
            $redis = Db_CacheRedis::getInstance($conf['host'] . ':' . $conf['port']);
            $redis->connect($conf['host'], $conf['port'], $conf['timeout']);
            if (!empty($conf['password'])) {
                $redis->auth($conf['password']);
            }
            $instances[$key] = $redis;
        }

        return $instances[$key];
    }


    const DB_PAY = 'db_pay';

    public static function getPayDao($table, $cacheTime = -1, $reconnect = false)
    {
        return self::_getDao($table, self::DB_PAY, $cacheTime, $reconnect);
    }

    public static function _getDao($table, $db, $cacheTime = -1, $reconnect = false)
    {
        static $instances = array();
        if (!isset($instances[$db])) {
            $instances[$db] = array();
        }
        $pdo = null;
        switch ($db) {
            case self::DB_PAY:
            default :
                $pdo = self::getPayInstance();
                break;
        }
        if (empty($pdo)) {
            throw new Exception_DbError(Exception_DbError::CONN_FAILED);
        }
        if (empty($instances[$db][$table]) || $reconnect) {
            unset($instances[$db][$table]);
            $dao = new Db_Dao($pdo, $table, $cacheTime);
            $instances[$db][$table] = $dao;
        }
        return $instances[$db][$table];
    }

    public static function getPayInstance($forceNew = NULL)
    {
        if ($forceNew) {
            return self::getMySQLInstance(self::$pay, $forceNew);
        }
        static $instance = null;

        if ($instance === null) {
            $instance = self::getMySQLInstance(self::$pay);
        }
        return $instance;
    }

    /**
     *
     * @param PDO $pdo
     */
    private static function _mysqlKeepAlive(&$pdo)
    {
        if (self::_isPdoAlive($pdo)) {
            return;
        }
        $pdoKey = array_search($pdo, self::$_mysqlInstances);
        $conf = self::$_mysqlConf[$pdoKey];
        $pdo = self::getMySQLInstance($conf, true);
    }

    public static function mysqlKeepAlive(&$pdo)
    {
        self::_mysqlKeepAlive($pdo);
    }

    public static function isPdoAlive($pdo)
    {
        return self::_isPdoAlive($pdo);
    }

    private static function _isPdoAlive($pdo)
    {
        static $expectedMsg = "mysql server has gone away";
        try {
            $res = $pdo->query("select version()");
            if (empty($res)) {
                return false;
            }
            $msg = $res->fetchColumn();
            $errorCode = $pdo->errorCode();
            if (intval($errorCode) && substr($errorCode, 0, 2) != '00') {
                Logger_Logger::instance()->logAlert(__METHOD__, 'keep_pdo_alive_error', $errorCode);
                return false;
            }
            return stripos($msg, $expectedMsg) === false;
        } catch (Exception $e) {
            $msg = $e->getMessage();
            Logger_Logger::instance()->logAlert(__METHOD__, 'keep_pdo_alive_exception', $msg);
            if (stripos($msg, $expectedMsg) !== false) {
                return false;
            }
            throw $e;
        }

        return true;
    }

    private static $_mysqlInstances = array();
    private static $_mysqlConf = array();

    public static function getMySQLInstance(array $server, $forceNew = false)
    {
        $connKey = http_build_query($server);
        if (isset(self::$_mysqlInstances[$connKey]) && !$forceNew) {
            return self::$_mysqlInstances[$connKey];
        }

        $pdo = self::_getMySQLInstance($server);

        self::$_mysqlConf[$connKey] = $server;
        self::$_mysqlInstances[$connKey] = $pdo;

        return $pdo;
    }

    private static function _getMySQLInstance(array $server)
    {
        $dsn = "mysql:host={$server['host']};port={$server['port']};dbname={$server['dbname']}";

        $options = array(PDO::ATTR_TIMEOUT => 2);
        $tryNum = 1;
        $maxTryNum = 3;
        $pdo = null;
        do {
            try {
                $pdo = new PDO($dsn, $server['username'], $server['password'], $options);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                break;
            } catch (Exception $e) {
                $tryNum++;
                Logger_Logger::instance()->logAlert(__METHOD__, 'mysql_lost' . "__" . $e->getMessage());
                if ($tryNum < $maxTryNum) {
                    usleep(100000);
                } else {
                    Logger_Logger::instance()->logAlert(__METHOD__, 'mysql_conn_failed' . "__" . $e->getMessage());
                    return null;
                }
                continue;
            }
        } while (true);

        if (isset($server['encoding'])) {
            $pdo->exec("set names {$server['encoding']}");
        }

        $timeout = Config_Env::getInstance()->isOnline() ? 30 : 60;
        $pdo->exec("set wait_timeout=$timeout");

        return $pdo;
    }

}

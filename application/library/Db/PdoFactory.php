<?php

class Db_PdoFactory
{

    private static $_TIMEOUT = 300;

    /**
     * PDO和连接串的对应关系
     * @var type
     */
    private $_pdoDsnMap = array();
    //当前有效的pdo
    private $_livePdos = array();

    public static function setTimeout($timeout)
    {
        self::$_TIMEOUT = $timeout;
    }

    public static function getInstance()
    {
        static $instance = NULL;
        if ($instance === NULL) {
            $instance = new self;
        }
        return $instance;
    }

    private function __construct()
    {

    }

    public function getPdo($server, $internal = false)
    {
        $conn = http_build_query($server);
        if (isset($this->_livePdos[$conn])) {
            $pdo = $this->_livePdos[$conn];
            $isAlive = $this->_checkPdoAlive($pdo);
            if ($isAlive) {
                return $pdo;
            }
        }

        if ($internal) {
            "real getting pdo." . http_build_query($server) . "\r\n";
        }

        return $this->_realGetPdo($server);
    }

    public function keepAlive(&$pdo)
    {
        return $pdo = $this->getPdo($this->_getServerByPdo($pdo), 1);
    }

    private function _getServerByPdo($pdo)
    {
        $pdoId = $this->_getPdoId($pdo);
        if (!isset($this->_pdoDsnMap[$pdoId])) {
            die('can not find pdo settings');
        }
        $dsn = $this->_pdoDsnMap[$pdoId];
        $server = array();
        parse_str($dsn, $server);
        return $server;
    }

    private function _checkPdoAlive($pdo)
    {
        $isAlive = $this->_isPdoAlive($pdo);
        if (!$isAlive) {
            //将pdo从alivePdo里删除
            $pos = array_search($pdo, $this->_livePdos);
            if ($pos !== false) {
                unset($this->_livePdos[$pos]);
            }
        }
        return $isAlive;
    }

    private function _isPdoAlive($pdo)
    {
        static $expectedMsg = "mysql server has gone away";
        static $expectedMsg2 = 'lost connection to mysql server during query';
        try {
            $res = $pdo->query("select 1");
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
            Logger_Logger::instance()->logAlert(__METHOD__, 'keep_pdo_alive_exception', $msg . ' ' . $e->getCode());
            if (stripos($msg, $expectedMsg) !== false) {
                return false;
            }
            if (stripos($msg, $expectedMsg) !== false) {
                return false;
            }
            if ($e->getCode() == 'HY000') {
                return false;
            }
            throw $e;
        }

        return true;
    }

    private function _realGetPdo($server)
    {
        $dsn = "mysql:host={$server['host']};port={$server['port']};dbname={$server['dbname']}";
        $options = array(PDO::ATTR_TIMEOUT => self::$_TIMEOUT);
        $tryNum = 1;
        $maxTryNum = 3;
        $pdo = null;
        do {
            try {
                $pdo = new PDO($dsn, $server['username'], $server['password'], $options);
                break;
            } catch (Exception $e) {
                $tryNum++;
                Logger_Logger::instance()->logAlert(__METHOD__, 'mysql_lost' . "__" . $e->getMessage());
                continue;
            }
            if ($tryNum < $maxTryNum) {
                usleep(100000);
            } else {
                Logger_Logger::instance()->logAlert(__METHOD__, 'mysql_conn_failed' . "__" . $e->getMessage());
                return null;
            }
        } while (true);

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        if (isset($server['encoding'])) {
            $pdo->exec("set names $server[encoding]");
        }
        if (isset($server['charset'])) {
            $pdo->exec("set names $server[charset]");
        }

        ini_set('mysql.connect_timeout', self::$_TIMEOUT);
        ini_set('default_socket_timeout', self::$_TIMEOUT);

        $pdo->exec("set wait_timeout=" . self::$_TIMEOUT);
        $connKey = http_build_query($server);
        $this->_pdoDsnMap[$this->_getPdoId($pdo)] = $connKey;
        $this->_livePdos[$connKey] = $pdo;
        return $pdo;
    }

    private function _getPdoId($pdo)
    {
        ob_start();
        var_dump($pdo);
        return md5(ob_get_clean());
    }

}

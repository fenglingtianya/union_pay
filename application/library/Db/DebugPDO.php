<?php

class Db_DebugPDO
{

    /**
     *
     * @var PDO 
     */
    private $_pdo;

    public function __construct($dsn, $username, $passwd, $options)
    {
        $this->_pdo = new PDO($dsn, $username, $passwd, $options);
    }

    const TYPE_SQL = 'sql';

    public function exec($sql)
    {
        self::initLogger();
        self::$_logger->log(self::TYPE_SQL, '[info] ' . $sql);
        $ret = $this->_pdo->exec($sql);
        if (!$ret) {
            self::$_logger->log(self::TYPE_SQL, '[error] ' . $this->_getErrorInfo());
        }
        return $ret;
    }
    
    private function _getErrorInfo()
    {
        return var_export($this->_pdo->errorInfo(),1);
    }

    public function query($sql)
    {
        self::initLogger();
        self::$_logger->log(self::TYPE_SQL, '[info] ' . $sql);
        $ret = $this->_pdo->query($sql);
        if (!$ret) {
            self::$_logger->log(self::TYPE_SQL, '[error] ' . $this->_getErrorInfo());
        }
        return $ret;
    }

    /**
     *
     * @var Logger_Default 
     */
    private static $_logger = null;

    protected static function initLogger($logger = null)
    {
        if (self::$_logger && $logger === null) {
            return;
        }

        if ($logger) {
            self::$_logger = $logger;
            return;
        }

        if (self::$_logger === null) {
            self::$_logger = new Logger_Default();
        }
        return self::$_logger;
    }

    public function prepare($statement, array $driver_options = array())
    {
        self::initLogger();
        $stmt = $this->_pdo->prepare($statement, $driver_options);

        if (!$stmt) {
            self::$_logger->log(self::TYPE_SQL, "[error] " . $statement . "\r\n" .
                $this->_getErrorInfo()
            );
            //should throw exception........
            return null;
        }
        return new Db_DebugStatement($stmt);
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array(array($this->_pdo, $name), $arguments);
    }

}

?>

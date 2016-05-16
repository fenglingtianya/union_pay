<?php

/**
 * 双写或多写的pdo，select读取只从第一个pdo中查询
 */
class Db_MulPdo
{

    private $_pdoList = array();

    /**
     * 
     * @param array $pdoList 多个pdo
     */
    public function __construct(array $pdoList)
    {
        $this->_pdoList = $pdoList;
        self::initLogger();
    }

    public function getPdoList()
    {
        return $this->_pdoList;
    }

    public function lastInsertIds()
    {
        $ids = array();
        foreach ($this->_pdoList as $pdo) {
            $ids[] = $pdo->lastInsertId();
        }

        return $ids;
    }

    public function exec($sql)
    {
        $ret = false;
        $hasErr = false;
        $pdoList = $this->_getPdoList($sql);
        foreach ($pdoList as $pdo) {
            $ret = $pdo->exec($sql);
            if (empty($ret)) {
                $hasErr = true;
            }
        }
        if ($hasErr) {
            $errors = $this->_getErrors($pdoList);
            $errors['sql'] = $sql;
            self::$_logger->logAlert(__METHOD__, 'sql_error', $errors);
        }
        return $ret;
    }

    private function _isSelect($sql)
    {
        $sql = trim($sql);
        if (preg_match('#^\s*select\s+#i', $sql)) {
            return true;
        }
        return false;
    }

    /**
     * 根据sql语句决定是否使用多个pdo
     * 注意多个pdo中的第一个将会被用来执行select查询，其它会被忽略。
     * @param string $sql
     * @return array pdo列表
     */
    private function _getPdoList($sql)
    {
        $pdoList = $this->_pdoList;
        if ($this->_isSelect($sql)) {
            $pdoList = array_slice($this->_pdoList, 0, 1);
        }
        return $pdoList;
    }

    private function _getErrors($pdoList = null)
    {
        if ($pdoList === null) {
            $pdoList = $this->_pdoList;
        }
        $errors = array();
        foreach ($pdoList as $pdo) {
            $errors[] = $pdo->errorInfo();
        }
        return $errors;
    }

    public function query($sql)
    {
        $pdoList = $this->_getPdoList($sql);
        $hasErr = false;
        foreach ($pdoList as $pdo) {
            $ret = $pdo->query($sql);
            if (!$ret) {
                $hasErr = true;
            }
        }

        if ($hasErr) {
            self::$_logger->logAlert(__METHOD__, 'sql_error', $this->_getErrors($pdoList));
        }
        return $ret;
    }

    /**
     *
     * @var Logger_Logger 
     */
    private static $_logger = null;

    protected static function initLogger()
    {
        if (self::$_logger === null) {
            self::$_logger = Logger_Logger::instance();
        }
        return self::$_logger;
    }

    public function prepare($statement, array $driver_options = array())
    {
        $stmtList = array();
        $pdoList = $this->_getPdoList($statement);
        foreach ($pdoList as $pdo) {
            $stmt = $pdo->prepare($statement, $driver_options);
            if (!$stmt) {
                $errors = $this->_getErrors($pdoList);
                $errors['sql'] = $statement;
                self::$_logger->logAlert(__METHOD__, 'sql_error', $errors);
                //should throw exception........
                return false;
            }

            $stmtList[] = $stmt;
        }

        return new Db_MulStatement($stmtList);
    }

    public function __call($name, $arguments)
    {
        $ret = false;
        $retVals = array();
        foreach ($this->_pdoList as $pdo) {
            $ret = call_user_func_array(array($pdo, $name), $arguments);
            $retVals[] = $ret;
        }

        $hasErr = false;
        $lastRetVal = $retVals[0];
        foreach ($retVals as $retVal) {
            if ($lastRetVal !== $retVal) {
                $hasErr = true;
            }
        }

        if ($hasErr) {
            self::$_logger->logAlert(__METHOD__, 'sql_error', func_get_args());
        }
        return $ret;
    }

}

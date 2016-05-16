<?php

class Db_MulStatement
{

    /**
     *
     * @var PDOStatement 
     */
    private $_stmtList;

    public function __construct($stmtList)
    {
        $this->_stmtList = $stmtList;
        self::_initLogger();
    }

    public function bindValue($parameter, $value, int $data_type = null)
    {
        foreach ($this->_stmtList as $stmt) {
            $ret = $stmt->bindValue($parameter, $value, $data_type);
        }
        return $ret;
    }

    public function execute(array $input_parameters = null)
    {
        $hasErr = false;
        foreach ($this->_stmtList as $stmt) {
            $ret = $stmt->execute($input_parameters);
            if ($ret === false) {
                $hasErr = true;
            }
        }
        if ($hasErr) {
            throw new Exception(var_export($this->_getErrors(),1));
        }
        return $ret;
    }

    private function _getErrors()
    {
        $errs = array();
        foreach ($this->_stmtList as $stmt) {
            $errs[] = $stmt->errorInfo();
        }
        return $errs;
    }

    /**
     *
     * @var Logger_Logger
     */
    private static $_logger = null;

    private static function _initLogger()
    {
        if (self::$_logger === null) {
            self::$_logger = Logger_Logger::instance();
        }
        return self::$_logger;
    }

    public function __call($name, $arguments)
    {
        $ret = false;
        $retVals = array();
        foreach ($this->_stmtList as $stmt) {
            $ret = call_user_func_array(array($stmt, $name), $arguments);
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
            self::$_logger->logAlert(__METHOD__,'sql error', "[error] " . var_export(func_get_args(), 1) . "\r\n" .
                $this->_getErrorInfo()
            );
        }
        return $ret;
    }

}


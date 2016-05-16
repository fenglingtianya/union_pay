<?php

class Db_DebugStatement
{

    /**
     *
     * @var PDOStatement 
     */
    private $_bindValues = array();
    private $_pdoStatement;

    public function __construct($pdoStatement)
    {
        $this->_pdoStatement = $pdoStatement;
    }

    public function bindValue($parameter, $value, int $data_type = null)
    {
        if ($data_type === null) {
            $data_type = PDO::PARAM_STR;
        }
        $ret = $this->_pdoStatement->bindValue($parameter, $value, $data_type);
        if ($ret) {
            $this->_bindValues[$parameter] = $value;
        }
        return $ret;
    }

    public function execute(array $input_parameters = null)
    {
        if (!empty($input_parameters)) {
            $this->_bindValues = array_merge($this->_bindValues, $input_parameters);
        }

        $time = microtime(true);
        $ret = $this->_pdoStatement->execute($input_parameters);
        $usedTime = microtime(true) - $time;
        $this->_debug($usedTime);
        if ($ret === false) {
            self::$_logger->log(self::TYPE_SQL, '[error] ' . var_export($this->_pdoStatement->errorInfo(),1));
        }
        return $ret;
    }

    private static $_sqlIndex = 1;

    private function _debug($usedTime)
    {
        $logger = self::_getLogger();
        if (self::$_sqlIndex == 1) {
            $logger->log(self::TYPE_SQL, "\r\n\r\n\r\n new instance\r\n");
        }
        $search = array();
        $replace = array();
        krsort($this->_bindValues);
        foreach ($this->_bindValues as $k => $v) {
            $search[] = "#{$k}#";
            $replace[] = "'" . $v . "'";
        }
        $sql = preg_replace($search, $replace, $this->_pdoStatement->queryString);

        $logger->log(self::TYPE_SQL, self::$_sqlIndex . " " . $usedTime . "");
        $logger->log(self::TYPE_SQL, $sql . "");
        self::$_sqlIndex++;
    }

    const TYPE_SQL = 'sql';

    /**
     *
     * @var Logger_Default
     */
    private static $_logger = null;

    private static function _getLogger()
    {
        if (self::$_logger === null) {
            self::$_logger = new Logger_Default();
        }
        return self::$_logger;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array(array($this->_pdoStatement, $name), $arguments);
    }

}

?>

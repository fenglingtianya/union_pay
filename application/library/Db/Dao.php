<?php

class Db_Dao
{

    private $_pdo;
    private $_tableName;
    private $_cacheTime;
    private $_useMaster = false;
    private $_useCache = false;

    public function __construct($pdo, $tableName, $cacheTime = -1)
    {
        $this->_pdo = $pdo;
        $this->_tableName = $tableName;
        $this->_cacheTime = $cacheTime;
    }

    public function useMaster()
    {
        $this->_useMaster = true;
    }

    public function useSlave()
    {
        $this->_useMaster = false;
    }

    /**
     * 入库
     */
    public function insert($arr, $table = null)
    {
        if ($table === null) {
            $table = $this->_tableName;
        }
        $keys = array_keys($arr);
        $strFields = implode(',', $keys);
        $strBindParam = ':' . implode(',:', $keys);

        $strSql = 'INSERT INTO ' . $table . '(' . $strFields . ') VALUES(' . $strBindParam . ')';
        $pstmt = $this->_pdo->prepare($strSql);

        $arrBindInfo = array();
        foreach ($arr as $key => $val) {
            $arrBindInfo[':' . $key] = $val;
        }

        try {
            $ret = $pstmt->execute($arrBindInfo);
            $lastInsertId = 0;
            if ($ret) {
                $lastInsertId = $this->_pdo->lastInsertId();
            }

            if ($lastInsertId) {
                $ret = $lastInsertId;
            }
        } catch (Exception $e) {
            $ret = false;
            Logger_Logger::instance()->logAlert(__METHOD__, 'sql_error', array('sql' => $strSql, 'err' => $e->getMessage()));
        }

        return $ret;
    }

    public function updateEx($arr, $update, $condition, $params = NULL)
    {
        $bindParams = array();
        if ($params === NULL) {
            $params = array();
        }
        $strSql = "update $this->_tableName set ";

        foreach ($arr as $key => $val) {
            $strSql .= "$key=?,";
            $bindParams[] = $val;
        }

        foreach ($update as $key => $val) {
            $strSql .= "$key=$key+?,";
            $bindParams[] = $val;
        }
        $sql = substr($strSql, 0, -1);

        $where = $condition;
        if ($condition && stripos(trim($condition), 'where') !== 0) {
            $where = "where " . $condition;
        }

        $sql .= " " . $where . " ";
        $pstmt = $this->_pdo->prepare($sql);
        try {
            $ret = $pstmt->execute(array_merge($bindParams, $params));
            if ($ret) {
                $ret = $pstmt->rowCount();
            }
        } catch (Exception $e) {
            $ret = false;
            Logger_Logger::instance()->logAlert(__METHOD__, 'sql_error', array('sql' => $strSql, 'err' => $e->getMessage()));
        }
        return $ret;
    }

    public function update($arr, $condition, $params = NULL)
    {
        return self::updateEx($arr, array(), $condition, $params);
        $strSql = "update $this->_tableName set ";

        $arrBindInfo = array();
        foreach ($arr as $key => $val) {
            $bindField = ":$key";
            $strSql .= "$key=$bindField,";
            $arrBindInfo[$bindField] = $val;
        }
        $sql = substr($strSql, 0, -1);

        $where = $condition;
        if ($condition && stripos(trim($condition), 'where') !== 0) {
            $where = "where " . $condition;
        }

        $sql .= " " . $where . " ";
        $pstmt = $this->_pdo->prepare($sql);
        try {
            $ret = $pstmt->execute($arrBindInfo);
        } catch (Exception $e) {
            $ret = false;
            Logger_Logger::instance()->logAlert(__METHOD__, 'sql_error', array('sql' => $strSql, 'err' => $e->getMessage()));
        }
        return $ret;
    }

    public function delete($condition, $params = NULL, $table = null)
    {
        $where = $condition;
        if ($params === NULL) {
            $params = array();
        }
        if ($condition && stripos(trim($condition), 'where ') !== 0) {
            $where = "where " . $condition;
        }
        if ($table === null) {
            $table = $this->_tableName;
        }
        $sql = "delete from $table $where";

        try {
            $stmt = $this->_pdo->prepare($sql);
            $ret = $stmt->execute($params);
        } catch (Exception $e) {
            $ret = false;
            Logger_Logger::instance()->logAlert(__METHOD__, 'sql_error', array('sql' => $sql, 'err' => $e->getMessage()));
        }

        return $ret;
    }

    public function queryAllBySql($sql, $params = null, $cacheTime = null, $cacheVersion = null)
    {
        $cacheTime = $this->_getCacheTime($cacheTime);
        $cacheable = $this->_isCacheable($cacheTime);
        $cacheKey = '';
        if ($cacheable) {
            $cacheKey = __METHOD__ . '::' . var_export(func_get_args(), true);
            $result = $this->_getFromCache($cacheKey);

            if ($result !== FALSE) {
                return $result;
            }
        }

        if ($params === null) {
            $params = array();
        }
        try {
            $this->_modifySql($sql);
            $sth = $this->_pdo->prepare($sql);
            $sth->execute($params);
            $result = $sth->fetchAll(PDO::FETCH_ASSOC);
            if ($result === false) {
                $result = array();
            }
            $this->_saveToCache($cacheKey, $cacheTime, $result);
        } catch (Exception $e) {
            $result = false;
            Logger_Logger::instance()->logAlert(__METHOD__, 'sql_error', array('sql' => $sql, 'err' => $e->getMessage()));
        }

        return $result;
    }

    private function _modifySql(&$sql)
    {
        if ($this->_useMaster) {
            $sql = '/*master*/' . $sql;
        }
        return $sql;
    }

    public function queryAll($moreSql, $params = null, $fields = null, $cacheTime = null, $cacheVersion = null)
    {
        if ($fields === null) {
            $fields = ' * ';
        }
        $sql = "select $fields from $this->_tableName $moreSql";
        return $this->queryAllBySql($sql, $params, $cacheTime, $cacheVersion);
    }

    public function queryRowBySql($sql, $params = null, $cacheTime = null, $cacheVersion = null)
    {
        $cacheTime = $this->_getCacheTime($cacheTime);
        $cacheable = $this->_isCacheable($cacheTime);
        $cacheKey = '';
        if ($cacheable) {
            $cacheKey = __METHOD__ . '::' . var_export(func_get_args(), true);
            $result = $this->_getFromCache($cacheKey);

            if ($result !== FALSE) {
                return $result;
            }
        }
        if ($params === NULL) {
            $params = array();
        }

        try {
            $this->_modifySql($sql);
            $sth = $this->_pdo->prepare($sql);
            $sth->execute($params);
            $result = $sth->fetch(PDO::FETCH_ASSOC);
            if ($result === false) {
                $result = array();
            } else {
                $this->_saveToCache($cacheKey, $cacheTime, $result);
            }
        } catch (Exception $e) {
            $result = false;
            Logger_Logger::instance()->logAlert(__METHOD__, 'sql_error', array('sql' => $sql, 'err' => $e->getMessage()));
        }

        return $result;
    }

    public function queryRow($moreSql, $params = null, $fields = '*', $cacheTime = null, $cacheVersion = null)
    {
        $sql = "select $fields from $this->_tableName $moreSql";
        return $this->queryRowBySql($sql, $params, $cacheTime, $cacheVersion);
    }

    public function queryOneBySql($sql, $params = null, $cacheTime = null, $cacheVersion = null)
    {
        $row = $this->queryRowBySql($sql, $params, $cacheTime, $cacheVersion);
        if (empty($row)) {
            return false;
        }
        $values = array_values($row);
        return $values[0];
    }

    public function queryOne($moreSql, $params = null, $field = '*', $cacheTime = null, $cacheVersion = null)
    {
        $sql = "select $field from $this->_tableName $moreSql";
        return $this->queryOneBySql($sql, $params, $cacheTime, $cacheVersion);
    }

    public function execute($sql)
    {
        try {
            $ret = $this->_pdo->exec($sql);
        } catch (Exception $e) {
            $ret = false;
            Logger_Logger::instance()->logAlert(__METHOD__, 'sql_error', array('sql' => $sql, 'err' => $e->getMessage()));
        }

        return $ret;
    }

    /**
     *
     * @param String $sql
     * @param array $params
     * @return PDOStatement
     */
    public function query($sql, $params = NULL)
    {
        $pdo = $this->_pdo;
        Helper_Factory::mysqlKeepAlive($pdo);
        $stmt = $pdo->prepare($sql);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        if ($params === null) {
            $params = array();
        }
        $stmt->execute($params);
        return $stmt;
    }

    #####################cache related begins##################################

    public function enableCache($enable = NULL)
    {
        $this->_useCache = $enable;
    }

    /**
     * if current cachetime is undefined/NULL, return current dao default cacheTime
     * @param int $cacheTime
     * @return int
     */
    private function _getCacheTime($cacheTime)
    {
        if ($cacheTime === NULL) {
            $cacheTime = $this->_cacheTime;
        }

        $cacheTime = intval($cacheTime);
        if ($cacheTime < 0) {
            $cacheTime = 0;
        }
        return $cacheTime;
    }

    /**
     * cacheable according to cachetime
     * @param int $cacheTime
     * @return boolean
     */
    private function _isCacheable($cacheTime)
    {
        if (!$this->_useCache) {
            return false;
        }
        return $cacheTime > 0;
    }

    /**
     * add dao::$tableName prefix to cacheKey
     * @param string $orignalCacheKey
     * @return string
     */
    private function _getCacheKey($orignalCacheKey)
    {
        return md5("dao::" . $this->_tableName . "::" . $orignalCacheKey);
    }

    /**
     * read data from cache
     * @param string $cacheKey(original cacheKey)
     */
    private function _getFromCache($cacheKey)
    {
        $cacheKey = $this->_getCacheKey($cacheKey);
        $cache = Helper_Factory::getCacheInstance();

        if ($cache === false) {
            return false;
        }
        //TODO::optimize this??
        return @unserialize($cache->get($cacheKey));
    }

    /**
     * save data to cache
     * @param string $cacheKey(original cache key without dao::$tableName prefix)
     * @param int $cacheTime
     * @param mixed $data
     * @return boolean
     */
    private function _saveToCache($cacheKey, $cacheTime, $data)
    {
        if (!$this->_isCacheable($cacheTime)) {
            return;
        }
        $cacheKey = $this->_getCacheKey($cacheKey);
        $cache = Helper_Factory::getCacheInstance();
        $cache->setex($cacheKey, $cacheTime, serialize($data));
    }

    public function isPdoAlive()
    {
        return Helper_Factory::isPdoAlive($this->_pdo);
    }

    #####################cache related ends##################################
}

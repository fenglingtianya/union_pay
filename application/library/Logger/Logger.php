<?php

class Logger_Logger
{
    const LOG_TYPE = "openapi";
    //DEBUG Level指出细粒度信息事件对调试应用程序是非常有帮助的。
    const DEBUG_TYPE = "DEBUG";
    //INFO level表明 消息在粗粒度级别上突出强调应用程序的运行过程。
    const INFO_TYPE = "INFO";
    //WARN|ALERT level表明会出现潜在错误的情形。
    const ALERT_TYPE = "ALERT";
    //ERROR level指出虽然发生错误事件，但仍然不影响系统的继续运行。
    const ERROR_TYPE = "ERROR";
    //FATAL level指出每个严重的错误事件将会导致应用程序的退出。
    const FATAL_TYPE = "FATAL";
    //STAT 统计
    const STAT_TYPE = "STAT";
    const ARRAY_SEPARATER = "|";
    const FIELD_SEPARATER = "\t";

    private static $instance = null;
    private $file = null;
    private $syslog = null;

    private function __construct()
    {
        $this->file = new Logger_File();
//        $this->syslog = new Logger_Syslog();
    }

    /**
     *
     * @return Logger_Logger
     */
    public static function instance()
    {

        if (!isset(self::$instance)) {
            $class = __class__;
            self::$instance = new $class;
        }
        return self::$instance;
    }

    public function logDebug($location, $tag, $info)
    {
        $this->writeWithNewline(self::DEBUG_TYPE, $location, $tag, $info);
    }

    public function logInfo($location, $tag, $info)
    {
        $this->writeWithoutNewline(self::INFO_TYPE, $location, $tag, $info);
    }

    public function logWarn($location, $tag, $info = '')
    {
        $this->writeWithoutNewline(self::ALERT_TYPE, $location, $tag, $info);
    }

    public function logAlert($location, $tag, $info = '')
    {
        $this->logWarn($location, $tag, $info);
    }

    public function logError($location, $tag, $info)
    {
        $this->writeWithoutNewline(self::ERROR_TYPE, $location, $tag, $info);
    }

    public function logFatal($location, $tag, $info)
    {
        $this->writeWithoutNewline(self::FATAL_TYPE, $location, $tag, $info);
    }

    /**
     * 统计打点
     * @param type $location
     * @param type $tag
     * @param type $info
     */
    public function logStat($location, $tag, $info)
    {
        $this->writeWithoutNewline(self::STAT_TYPE, $location, $tag, $info);
    }

    private function convertWithoutNewline($info)
    {
        $ret = '';
        if (!is_array($info)) {
            $ret = $info;
        } else {
            $ret = '{|';
            foreach ($info as $key => $value) {
                $ret .= $key . '=' . $this->convertWithoutNewline($value) . self::ARRAY_SEPARATER;
            }
            $ret = substr($ret, 0, -1) . '|}';
        }
        return str_replace("/n", '/n', $ret);
    }

    private function convertWithNewline($info)
    {
        return var_export($info, true) . "\n";
    }

    private function writeWithoutNewline($type, $location, $tag, $info)
    {
        $msg = date('Y-m-d H:i:s T') . self::FIELD_SEPARATER . $type . self::FIELD_SEPARATER .
            $location . self::FIELD_SEPARATER . $tag . self::FIELD_SEPARATER . $this->convertWithoutNewline($info);
        $this->file && $this->file->log($type, $msg);
        $this->syslog && $this->syslog->log($type, $msg);
    }

    private function writeWithNewline($type, $location, $tag, $info)
    {
        $msg = date('Y-m-d H:i:s T') . self::FIELD_SEPARATER . $type . self::FIELD_SEPARATER .
            $location . self::FIELD_SEPARATER . $tag . self::FIELD_SEPARATER . $this->convertWithNewline($info);
        $this->file && $this->file->log($type, $msg);
        $this->syslog && $this->syslog->log($type, $msg);
    }

}

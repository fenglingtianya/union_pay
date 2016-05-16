<?php

class Logger_File
{

    const LOG_PATH = "/data/union_pay/biz_logs/";

    //日志级别和对应的文件
    private static $_levelMap = array(
        Logger_Logger::ALERT_TYPE => 'error',
        Logger_Logger::ERROR_TYPE => 'error',
        Logger_Logger::FATAL_TYPE => 'error',
        Logger_Logger::INFO_TYPE => 'info',
        Logger_Logger::DEBUG_TYPE => 'debug',
        Logger_Logger::STAT_TYPE => 'stat',
    );

    public function log($type, $msg)
    {
        $logFile = $this->getPath($type);
        $isNewFile = !file_exists($logFile);
        $fp = fopen($logFile, 'a');
        if (flock($fp, LOCK_EX)) {
            if ($isNewFile) {
                chmod($logFile, 0666);
            }
            fwrite($fp, $msg . "\n");
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    }

    protected function getPath($type)
    {
        $logFileType = 'unknown';
        if (isset(self::$_levelMap[$type])) {
            $logFileType = self::$_levelMap[$type];
        }
        $date = date('Ymd');
        return self::LOG_PATH . "/{$logFileType}.log.{$date}";
    }

}

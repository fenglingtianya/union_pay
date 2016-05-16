<?php

/**
 * 使用rsyslog兼容日志。需要在 /etc/rsyslog.conf 设置
 */
class Logger_Syslog
{
    public function log($type, $msg)
    {
        $logType = self::_getLogType($type);
        syslog($logType | LOG_LOCAL0, $msg);
    }

    private static function _getLogType($type)
    {

        switch ($type) {
            case 'DEBUG':
                return LOG_DEBUG;
            case 'ALERT':
                return LOG_ALERT;
            case 'ERROR':
                return LOG_ERR;
            case 'FATAL':
                return LOG_EMERG;
            case 'STAT':
                return LOG_CRIT;
            case 'INFO':
            default:
                return LOG_NOTICE;
        }
    }

}

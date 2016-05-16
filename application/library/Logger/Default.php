<?php

class Logger_Default
{

    public function __construct()
    {

    }

    static function test()
    {
        echo __class__;
    }

    public function log($type, $msg)
    {
        $logFile = $this->getPath($type);
        $isNewFile = !file_exists($logFile);
        $fp = fopen($logFile, 'a');
        if (flock($fp, LOCK_EX)) {
            if ($isNewFile) {
                chmod($logFile, 0666);
                //chown($logFile, 'nobody');
            }
            fwrite($fp, $msg . "\n");
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    }

    protected function getPath($type)
    {
        return '/data/union_pay/biz_logs/' . date('Ymd') . '_' . strtolower($type) . '.log';
    }

}

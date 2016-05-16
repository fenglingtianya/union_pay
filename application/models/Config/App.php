<?php

class Config_AppModel
{

    private static $_testApp = array(
        ''
    );

    public static function getInstance()
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }

    public function isTestApp($appKey)
    {
        return in_array($appKey, self::$_testApp);
    }

}

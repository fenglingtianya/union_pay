<?php

class Logger_Error
{

    public static function getInstance()
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }

    public function record()
    {
        error_reporting(E_ALL | E_STRICT);
        $displayError = Config_Env::getInstance()->isOnline() ? 'Off' : 'On';
        ini_set('display_errors', $displayError);

        register_shutdown_function(array($this, 'checkFatalError'));
    }

    public function checkFatalError()
    {
        $error = error_get_last();
        if ($error["type"] <= E_NOTICE || $error["type"] == E_STRICT) {
            $this->logPhpError($error["type"], $error["message"], $error["file"], $error["line"]);
        }
    }

    public function logPhpError($errLevel, $errMessage, $fileName, $lineNum)
    {
        //支持的错误等级
        static $_errorLevels = array(
            1 => "E_ERROR",
            2 => "E_WARNING",
            4 => "E_PARSE",
            8 => "E_NOTICE",
            16 => "E_CORE_ERROR",
            32 => "E_CORE_WARNING",
            64 => "E_COMPILE_ERROR",
            128 => "E_COMPILE_WARNING",
            256 => "E_USER_ERROR",
            512 => "E_USER_WARNING",
            1024 => "E_USER_NOTICE",
            2048 => 'E_STRICT',
        );

        //不处理没有包含在error_reporting中的错误等级
        if (!(error_reporting() & $errLevel)) {
            return FALSE;
        }
        
        if (!isset($_errorLevels[$errLevel])) {
            return FALSE;
        }

        $errMessage = str_replace(array("\r\n", "\n"), '<new line>', $errMessage);
        $scribeMessage = '`' . $errMessage . ' `' . $fileName . ' `' . $lineNum;
        Logger_Logger::instance()->logError('php_error', $_errorLevels[$errLevel], $scribeMessage);
        return TRUE;
    }

}

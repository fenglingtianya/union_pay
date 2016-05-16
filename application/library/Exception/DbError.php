<?php

class Exception_DbError extends Exception
{
    const CONN_FAILED = 'conn_failed';

    private static $_errors = array(
        self::CONN_FAILED => array(
            'code' => '2003',
            'msg' => '服务器忙，请重试',
        ),
    );

    public function __construct($key)
    {
        $error = self::$_errors[$key];
        $msg = $error['msg'] . '。错误代码:' . $error['code'];
        parent::__construct($msg, $error['code'], null);
    }

}

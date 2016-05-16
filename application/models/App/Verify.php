<?php

class App_VerifyModel
{
    public function getApp($appKey)
    {
        return array(
            'appsecret' => 'appsecret',
            'appkey' => $appKey,
            'appid' => 123,
            'appname' => 'testapp',
        );
    }

}

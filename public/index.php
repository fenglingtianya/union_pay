<?php

define('APPLICATION_PATH', dirname(dirname(__FILE__)));
$application = new Yaf_Application(APPLICATION_PATH . "/conf/application.ini");
Logger_Error::getInstance()->record();
$application->bootstrap()->run();
//$app->getDispatcher()->dispatch(new Yaf_Request_Simple());

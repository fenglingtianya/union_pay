<?php


var_dump(Yaf_Application::app());
exit;
$app = new Yaf_Application(APPLICATION_PATH . "/conf/application.ini");
$app->getDispatcher()->dispatch(new Yaf_Request_Simple());

<?php

class Base_Controller_App extends Yaf_Controller_Abstract
{

    protected function getUrlParts()
    {
        $uri = $this->getRequest()->getRequestUri();
        $urlPart = explode('/', $uri);
        $module = isset($urlPart[1]) ? $urlPart[1] : '';
        $controller = isset($urlPart[2]) ? $urlPart[2] : '';
        $action = isset($urlPart[3]) ? $urlPart[3] : '';
        return array(
            'module' => $module,
            'controller' => $controller,
            'action' => $action,
            'uri' => $uri,
        );
    }

    protected function renderJson($data)
    {
        Yaf_Dispatcher::getInstance()->disableView();
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }

    protected function getServerPart()
    {
        $server = $_SERVER;
        return array(
            'client_ip' => isset($server['REMOTE_ADDR']) ? $server['REMOTE_ADDR'] : '',
            'server_ip' => isset($server['SERVER_ADDR']) ? $server['SERVER_ADDR'] : '',
            'user_agent' => isset($server['HTTP_USER_AGENT']) ? $server['HTTP_USER_AGENT'] : '',
        );
    }

}

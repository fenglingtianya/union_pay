<?php

class ErrorController extends Base_Controller_App
{
	public function errorAction($exception)
    {
        $urlPart = $this->getUrlParts();

        $code = $exception->getCode();
        switch ($code) {
            case YAF_ERR_NOTFOUND_MODULE:
                $msg = 'invalid_module_' . $urlPart['module'];
                break;
            case YAF_ERR_NOTFOUND_CONTROLLER:
                $msg = 'invalid_controller_' . $urlPart['controller'];
                break;
            case YAF_ERR_NOTFOUND_ACTION:
                $msg = 'invalid_action_' . $urlPart['action'];
                break;
            default:
                $msg = $exception->getMessage();
                break;
        }

        echo $msg;
        return false;
    }

}

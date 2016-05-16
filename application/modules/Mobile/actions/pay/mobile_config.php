<?php

class Mobile_configAction extends Base_Action_App
{

    public function getParam()
    {
        parent::getParam();
    }

    public function checkParam()
    {
        parent::checkParam();
    }

    public function verify()
    {
        parent::verify();
    }

    public function main()
    {
        $model = new Mobile_ConfigModel();
        $this->data = array(
            'code' => 0,
            'msg' => '',
            'conf' => array(
                'channel_type' => $model->getConfig(),
            ),
        );
    }

}

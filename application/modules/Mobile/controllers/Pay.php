<?php

class PayController extends Base_Controller_App
{

    public $actions = array(
        "create_order" => "modules/Mobile/actions/pay/create_order.php",
        "agent_notify" => "modules/Mobile/actions/pay/agent_notify.php",
        "mobile_config" => "modules/Mobile/actions/pay/mobile_config.php",
    );

}

<?php

/**
 * 根据bank code 分发到不同的channel
 */
class Channel_FactoryModel
{

    private static $_channelMap = array(
        Config_PayTypeModel::KUAIFA => 'Channel_AgentModel',
        Config_PayTypeModel::YIWAN => 'Channel_AgentModel',
    );
    private static $_instances = array();

    /**
     *
     * @param String $bankcode
     * @return Payment_Model_Channel_Base
     */
    public static function getInstance($bankcode)
    {
        if (!isset(self::$_channelMap[$bankcode])) {
            throw new Exception_BadRequest("bad bank_code " . $bankcode);
        }
        $channelClass = self::$_channelMap[$bankcode];
        if (!isset(self::$_instances[$channelClass])) {
            self::$_instances[$channelClass] = new $channelClass;
        }
        return self::$_instances[$channelClass];
    }

}

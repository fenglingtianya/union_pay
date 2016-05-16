<?php

class Create_orderAction extends Base_Action_App
{
    /**
     *
     * @var Vo_PayRequestModel
     */
    protected $_request;

    protected function getParam()
    {
        $this->_request = new Vo_PayRequestModel($this->requestParams);
    }

    protected function checkParam()
    {
        $mandatory = array(
            'app_key', 'user_id', 'product_id', 'product_name', 'agent_uid',
            'amount', 'notify_uri', 'app_order_id', 'channel_type',
            'app_uname', 'app_uid', 'access_token', 'sign'
        );

        foreach ($mandatory as $field) {
            if (empty($this->requestParams[$field])) {
                throw new Exception_BadRequest('no ' . $field);
            }
        }
    }

    protected function verify()
    {
        return;
        //验证app
        $app = $this->verifyApp($this->_request->appKey);

        //验证签名
        $params = Util_Sign::filter($this->requestParams, Config_Sign::getInstance()->getPayFields());
        $this->verifyClientSign($app, $params);

        //验证token
        $this->checkToken($this->_request->accessToken);
    }

    protected function main()
    {
        $this->_request->orderId = $this->_geneOrderId();

        $model = $this->_initModel();
        $model->pay($this->_request);
        $this->data = $model->getResult();
    }

    /**
     *
     * @return Channel_BaseModel
     */
    private function _initModel()
    {
        return Channel_FactoryModel::getInstance($this->_request->channelType);
    }

    private function _geneOrderId()
    {
        $orderPostfix = substr($this->_request->userId, -1);
        return Pay_OrderModel::generateOrderId($orderPostfix);
    }

}

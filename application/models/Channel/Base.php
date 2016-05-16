<?php

abstract class Channel_BaseModel
{

    protected $requestRecord;
    protected $app;
    protected $retData = array();
    protected $errorMsg = '';
    protected $errorCode = 0;

    /**
     *
     * @var Vo_PayRequestModel
     */
    protected $request;

    //请求第三方支付，抽象函数，由具体通道实现
    abstract protected function requestAgent();

    protected function checkAppOrder()
    {
        $request = $this->request;
        $table = Config_Db_Table::getTableByOrderId('pay_app_map', $request->orderId);
        $dao = Helper_Factory::getPayDao($table);
        $id = $dao->queryOne('where app_key=? and app_order_id=?', array($request->appKey, $request->appOrderId), 'id');
        if ($id) {//映射表中已存在该订单 说明为重复支付行为 抛出异常
            throw new Exception_BadRequest('repeat_apporderid::' . $request->appKey);
        }
        return true;
    }

    protected function beforeSaveOrder()
    {
        return true;
    }

    protected function saveOrder()
    {
        $model = new Pay_OrderModel();
        return $model->saveOrder($this->request);
    }

    protected function getCurl()
    {
        $request = $this->request;
        $curl = new Helper_Curl();
        $monitor = new Monitor_CurlLogger();
        $monitor->setParamSwitch(0);
        $monitor->setMark('union_create_order');
        $monitor->setExt(array(
            'app_key' => $request->appKey,
            'order_id' => $request->orderId,
            'pay_type' => $request->payType,
        ));
        $curl->setMonitor($monitor);
        return $curl;
    }

    /**
     *
     * @param Vo_PayRequestModel $request
     */
    public function pay($request)
    {
        $this->request = $request;

        $modelPlatform = new App_VerifyModel();
        $this->app = $modelPlatform->getApp($request->appKey);

        if (!$this->checkAppOrder()) {
            return;
        }

        if (!$this->beforeSaveOrder()) {
            return;
        }

        if (!$this->saveOrder()) {
            throw new Exception_InnerServer('failed to saveOrder');
        }

        $requestRecord = Util_RequestRecord::getInboundRequestRecord(
                $request->userId, $request->orderId, $request->appKey, $this->app['appid']
        );
        $this->requestRecord = $requestRecord;
        $this->retData = $this->requestAgent();
        $requestRecord->record();
    }

    public function getResult()
    {
        if ($this->errorCode) {
            return array(
                'code' => $this->errorCode,
                'msg' => $this->errorMsg,
            );
        }

        return $this->retData;
    }

}

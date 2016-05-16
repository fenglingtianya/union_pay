<?php

class Channel_AgentModel extends Channel_BaseModel
{

    protected function requestAgent()
    {
        $requestRecord = $this->requestRecord;
        $ret = array(
            'code' => 0,
            'msg' => '',
            'data' => array(
                'order_id' => $this->request->orderId,
            ),
        );
        $response = json_encode($ret);
        $requestRecord->setResponse($response);
        return $ret;
    }

}

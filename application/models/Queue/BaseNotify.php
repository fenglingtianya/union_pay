<?php

abstract class Queue_BaseNotifyModel
{
    const ERR_TYPE_CURL = 'curl'; //curl请求出错
    const ERR_TYPE_HTTP = 'http'; //http请求出错
    const ERR_TYPE_RESP = 'resp'; //http响应内容不正确
    const TIMEOUT = 15;
    
    const MAX_ROUND = 6; //通知次数最大阈值
    const RET_SUCC = 1; //通知成功
    const RET_CONTINUE = 2; //需要重复通知
    const RET_FAIL = 3; //当且仅当通知次数超过最大阈值，才算通知失败

    const GET_METHOND = 1;
    const POST_METHOD = 2;

    abstract protected function doJob();

    protected $task = array();

    public function processTask($task)
    {
        $this->task = $task;
        $isSucc = $this->doJob();
        if ($isSucc) {
            return self::RET_SUCC;
        }

        if ($this->isFailed()) {
            return self::RET_FAIL;
        }

        return self::RET_CONTINUE;
    }

    protected function isFailed()
    {
        return $this->task['run_num'] >= self::MAX_ROUND;
    }

}

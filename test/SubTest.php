<?php

/**
 * 测试 Nsq 的生产者
 *
 * @version  : 1.0.0
 * @datetime : 2019/3/20 08:11 08
 */

$lookupConf = [
    'host' => '192.168.1.51',
    'port' => 4161,
];

$nsqClient = new \nsqphp\NsqClient();

$topic = "test";
$channel = "test";

$nsqClient->subscribe($lookupConf,$topic,$channel,$this->tempCallback());

function tempCallback($conn,\nsqphp\Util\ResponseMessage $msg){
    echo $msg->getId().":".$msg->getPayload();
}
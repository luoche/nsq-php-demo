<?php

/**
 * 测试 Nsq 的生产者
 *
 * @version  : 1.0.0
 * @datetime : 2019/3/20 08:11 08
 */

$nsqConf = [
    ['host' => '192.168.1.50', 'port' => 4151],
    ['host' => '192.168.1.51', 'port' => 4151]
];

$nsqClient = new \nsqphp\NsqClient();
$nsqClient->publishTo($nsqConf);

$topic = "test";
$message = "Hello Nsq";

$nsqClient->publish($topic,$message);


// 发送多条消息
$topic = "test";
$message = [
    "message 1",
    "message 2",
];

$nsqClient->publish($topic,$message);
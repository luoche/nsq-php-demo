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
$pubClientNum = 1;
$nsqClient->publishTo($nsqConf,$pubClientNum);

$topic = "test";
$message = "Hello Nsq";

// 长连接内可以发送多条消息。
// 所谓的长连接就是 本new(实例化对象)中,用完没有关闭
$nsqClient->publish($topic,$message);

$nsqClient->publish($topic,$message);


// 发送多条消息
$topic = "test";
$message = [
    "message 1",
    "message 2",
];

$nsqClient->publish($topic,$message);


// php-fpm 常驻内容(比如系统内容常驻42个),经过nginx 转发过来,处理请求。
// 新的request 排队等待,分配上的匹配一个php-fpm,用来释放。

// 新的初始化对象,新开了一个新的对象,与上面的连接并不会复用
$nsqClientOther = new \nsqphp\NsqClient();
$nsqClientOther->publishTo($nsqConf);

$nsqClientOther->publish($topic,$message);
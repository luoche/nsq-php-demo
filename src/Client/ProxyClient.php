<?php
namespace nsqphp\Client;

/**
 * 模拟实现 发送接收消息
 *
 * @version  : 1.0.0
 * @datetime : 2019/3/20 08:29 08
 */
interface ProxyClient {

    public function read(int $length): string ;

    public function write(string $buffer);

    public function reconnect();
}
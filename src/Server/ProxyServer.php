<?php
namespace nsqphp\Server;

/**
 * 模拟实现 发送接收消息
 *
 * @version  : 1.0.0
 * @datetime : 2019/3/21 21:29 08
 */
interface ProxyServer {

    public function read(int $length): string ;

    public function write(string $buffer);

    public function reconnect();
}
<?php
namespace nsqphp\Client;

/**
 * 使用 Stream 实现消息体的发送和接收
 *
 * @version  : 1.0.0
 * @datetime : 2019/3/20 08:31 08
 */
class TcpClient  extends AbstractProxyClient   {

    public function read():string {
        return "";
    }

    public function write(string $buffer) {

    }

    public function reconnect() {

    }
}
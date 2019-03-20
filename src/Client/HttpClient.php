<?php
namespace nsqphp\Client;

/**
 * Http 实现消息的发送和接受
 *
 * @version  : 1.0.0
 * @datetime : 2019/3/20 08:31 08
 */
class HttpClient extends AbstractProxyClient  {

    public function read():string {
        return "";
    }

    public function write(string $buffer) {

    }

    public function reconnect() {

    }
}
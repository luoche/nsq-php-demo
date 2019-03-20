<?php
namespace nsqphp\Client;

/**
 * swoole 实现模拟请求
 *
 * @version  : 1.0.0
 * @datetime : 2019/3/20 08:31 08
 */
class SwooleClient extends AbstractProxyClient {

    public function read():string {
        return "";
    }

    public function write(string $buffer) {

    }

    public function reconnect() {

    }
}
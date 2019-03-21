<?php
namespace nsqphp\Client;

/**
 * swoole 实现模拟请求
 *
 * @version  : 1.0.0
 * @datetime : 2019/3/20 08:31 08
 */
class SwooleClient extends AbstractProxyClient {

    /**
     * TCP的默认连接是 4150
     *
     * @var int
     */
    public $port = 4150;

    public function read(int $length):string {
        return "";
    }

    public function write(string $buffer) {

    }

    public function reconnect() {

    }
}
<?php
namespace nsqphp\Client;

/**
 * Http 实现消息的发送和接收
 *
 * @version  : 1.0.0
 * @datetime : 2019/3/20 08:31 08
 */
abstract class AbstractProxyClient implements ProxyClient {

    /**
     * host
     *
     * @var string
     */
    public $host;
    /**
     * port
     *
     * @var string
     */
    public $port;

    public function __construct($host = "localhost",$port = 4151) {
        $this->host = $host;
        $this->port = $port;
    }

    public function read(int $length):string {
        return "";
    }

    public function write(string $buffer) {

    }

    public function getDomain() {
        return $this->host . ':' . $this->port;
    }
}
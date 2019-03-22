<?php
namespace nsqphp\Server;

/**
 * Http 实现消息的发送和接受
 *
 * @version  : 1.0.0
 * @datetime : 2019/3/20 08:31 08
 */
abstract class AbstractProxyServer implements ProxyServer  {

    public $host;

    public $port;

    /**
     * 订阅的 topic
     *
     * @var string
     */
    protected $topic;
    /**
     * 订阅的 channel
     *
     * @var string
     */
    protected $channel;
    /**
     * 处理消息的回调函数
     *
     * @var string
     */
    protected $callback;

    public function __construct($host = "localhost",$port = 4151) {
        $this->host = $host;
        $this->port = $port;
    }


    /**
     * 设置主题参数
     *
     * @param string $topic
     * @param string $channel
     * @param        $callback
     */
    public function setParams(string $topic,string $channel,$callback) {
        $this->topic    = $topic;
        $this->channel  = $channel;
        $this->callback = $callback;
    }

    public function read(int $length):string {
        return "";
    }

    public function write(string $buffer) {

    }

    public function reconnect() {

    }

    public function getDomain() {
        return $this->host . ':' . $this->port;
    }
}
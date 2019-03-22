<?php
namespace nsqphp\Client;
use nsqphp\Util\HTTP;

/**
 * Http 实现消息的发送和接受
 *
 * @version  : 1.0.0
 * @datetime : 2019/3/20 08:31 08
 */
class HttpClient extends AbstractProxyClient  {

    /**
     * HTTP 的默认连接是 4151
     *
     * @var int
     */
    public $port = 4151;

    /**
     * nsqd 的 publish url
     *
     * @var string
     */
    private $nsqdUrl = "";

    public function setUrl($nsqdUrl){
        $this->nsqdUrl = $nsqdUrl;
    }

    public function read(int $length):string {
        return "";
    }

    /**
     * curl -d "<message>" http://127.0.0.1:4151/pub?topic=name
     *
     * @param string $buffer
     * @return array
     */
    public function write(string $buffer) {
        $url = sprintf('http://%s:%d/%s', $this->host, $this->port, $this->nsqdUrl);

        return HTTP::post($url, $buffer);
    }

}
<?php
namespace nsqphp\Client;
use nsqphp\Exception\NetworkSocketException;
use nsqphp\Util\NsqMessage;

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

    /**
     * @var
     */
    private $socket;

    /**
     * swoole 的默认配置
     *
     * @var array
     */
    protected $setting = [
        'open_length_check'     => true,
        'package_max_length'    => 2048000,
        'package_length_type'   => 'N',
        'package_length_offset' => 0,
        'package_body_offset'   => 4
    ];

    /**
     * 连接超时时间--可以传惨 todo
     *
     * @var int
     */
    private $timeout = 3;

    /**
     * 使用 swoole 写入 TCP 消息
     *
     * @param string $buffer
     */
    public function write(string $buffer) {
        $write = $this->socket->send($buffer);
        if ($write === false) {
            throw new NetworkSocketException("Failed to write message to ".$this->getDomain());
        }
    }

    /**
     * 使用 swoole 读取  TCP 消息
     *
     * @param int $length
     * @return string
     */
    public function read(int $length = 65535):string {
        // 一下子读取全部
        $msg = $this->socket->recv($length);
        if ($msg === false) {
            throw new NetworkSocketException("Failed to read message from ".$this->getDomain());
        } else if ($msg == ''){
            throw new NetworkSocketException("Read 0 Byte from ".$this->getDomain());
        }

        return $msg;
    }



    public function reconnect() {

    }

    /**
     * 建立连接
     *
     * @param bool $reopen
     */
    private function getSocket($reopen = false){
        // 使用 swoole 建立连接
        if (!$this->socket) {
            $this->socket = new \swoole_client(SWOOLE_TCP);

            $this->socket->set($this->setting);

            $reopen = true;
        }
        if ($reopen) {
            $this->socket->connect($this->host,$this->port,$this->timeout);

            $this->socket->send(NsqMessage::magic());
        }
    }
}
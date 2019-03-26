<?php
namespace nsqphp\Server;
use nsqphp\Exception\NetworkSocketException;
use nsqphp\Util\NsqMessage;

/**
 * TCP 实现模拟请求
 *
 * @version  : 1.0.0
 * @datetime : 2019/3/20 08:31 08
 */
class TcpServer extends AbstractProxyServer {

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
     * 连接超时时间--可以传参
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
     * 避免了 TCP 粘包
     * 所有的数据就是相当于一个水管的水,水不断的流过来。 就是一直读 一直读 读到自己定义的协议结束。
     * 如果前期包的协议错误,那么就不是自己想要的包,直接丢弃 不做处理
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

    /**
     * 解析消息体的模板
     */
    public function dispatchFrame() {
        // 如果不读信息 传入 $conn
        // 如果已经读出信息 传入 string
        // swoole 的是从 onmessage 返回的整条消息(先不考虑 TCP 粘包)
        // TCP 需要从 socket 中读取消息
        $message = $this->read();

        // 封装 tcp 和 swoole 的功能方法。 减少重复代码
        $this->readMessage($message);
    }

    /**
     * 建立连接
     *
     * @param bool $reopen
     */
    public function getSocket($reopen = false){
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
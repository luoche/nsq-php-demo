<?php
namespace nsqphp\Server;
use nsqphp\Exception\NetworkSocketException;
use nsqphp\Exception\NsqException;
use nsqphp\Logger\Logger;
use nsqphp\Message\Message;
use nsqphp\Util\NsqMessage;
use nsqphp\Util\TcpResponseParse;

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

    /**
     * socket 由继承类实现
     *
     * @var string
     */
    protected $socket;

    public function __construct($host = "localhost",$port = 4151) {
        $this->host = $host;
        $this->port = $port;
    }


    /**
     * 设置 topic 等参数
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

    public function getDomain() {
        return $this->host . ':' . $this->port;
    }

    public function readMessage(string $message) {
        $responseMessageFormat = TcpResponseParse::readFormatFromBuffer($message);
        // 区分不同 读取消息是一直读
        if (TcpResponseParse::isHeartBeat($responseMessageFormat)) {
            // 如果是心跳 就继续
            // 可以把这个封装成一个方法 read
            $this->write(NsqMessage::nop());
        } else if(TcpResponseParse::isMessage($responseMessageFormat)){
            $receiveMsg = new Message($responseMessageFormat);
            if (!is_callable($this->callback)) {
                throw new NsqException("Subscribe callback is not callable");
            }

            try {
                // 定义好的回调参数
                call_user_func($this->callback,$this->socket,$receiveMsg);
            }catch (\Exception $e){
                // 消息处理失败

                // 告知重新放入队列
                $this->write(NsqMessage::req($receiveMsg->getId(),3));

                $this->write(NsqMessage::rdy(1));
                Logger::ins()->alert("Deal Message failed ");
                throw new NsqException("Deal Message failed ");
            }

            //释放消息
            $this->write(NsqMessage::fin($receiveMsg->getId()));
            // ready
            $this->write(NsqMessage::rdy(1));

        } else if (TcpResponseParse::isOk($responseMessageFormat)){
            // 不做处理
        } else {
            throw new NetworkSocketException("Error frame type from received.");
        }
    }
}
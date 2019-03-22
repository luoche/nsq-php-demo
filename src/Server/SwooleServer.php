<?php
namespace nsqphp\Server;
use nsqphp\Exception\NetworkSocketException;
use nsqphp\Exception\NsqException;
use nsqphp\Logger\Logger;
use nsqphp\Util\NsqMessage;
use nsqphp\Util\ResponseMessage;
use nsqphp\Util\ResponseNsq;

/**
 * swoole 实现模拟请求
 *
 * @version  : 1.0.0
 * @datetime : 2019/3/20 08:31 08
 */
class SwooleServer extends AbstractProxyServer {

    /**
     * TCP的默认连接是 4150
     *
     * @var int
     */
    public $port = 4150;

    /**
     * @var
     */
    protected $socket;

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
     * 建立连接
     *
     * $client = new Swoole\Client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
     *    $client->on("connect", function(swoole_client $cli) {
     *          $cli->send("GET / HTTP/1.1\r\n\r\n");
     *    });
     *
     *    $client->on("receive", function(swoole_client $cli, $data){
     *          echo "Receive: $data";
     *          $cli->send(str_repeat('A', 100)."\n");
     *          sleep(1);
     *    });
     *
     *    $client->on("error", function(swoole_client $cli){
     *          echo "error\n";
     *    });
     *
     *    $client->on("close", function(swoole_client $cli){
     *         echo "Connection close\n";
     *    });
     *
     *    $client->connect('127.0.0.1', 9501);
     *
     * @param bool $reopen
     */
    public function getSocket($reopen = false){
        // 使用 swoole 建立连接
        if (!$this->socket) {
            $this->socket = new \swoole_client(SWOOLE_TCP, SWOOLE_SOCK_ASYNC);

            $this->socket->set($this->setting);

            $reopen = true;
        }
        if ($reopen) {
            $this->socket->connect($this->host,$this->port,$this->timeout);

            // 服务启动的时候 开始监听函数
            $this->socket->on("connect", [ $this, "onConnect" ]);
            $this->socket->on("receive", [ $this, "onMessage" ]);
            $this->socket->on("error", [ $this, "onError" ]);
            $this->socket->on("close", [ $this, "onClose" ]);
        }
    }

    /**
     * 建立连接
     *
     * $client->on("connect", function(swoole_client $cli) {
     *          $cli->send("GET / HTTP/1.1\r\n\r\n");
     *    });
     *
     * @param \swoole_client $client
     */
    private function onConnect(\swoole_client $client){
        // 建立连接的步骤
        // 1. 发送版本信息
        // 2. 发起订阅
        // 3. 告知 ready
        $this->socket->send(NsqMessage::magic());

        $this->socket->send(NsqMessage::sub($this->topic, $this->channel));

        $this->socket->send(NsqMessage::rdy(1));

    }

    /**
     * 接收消息
     *  $client->on("receive", function(swoole_client $cli, $data){
     *          echo "Receive: $data";
     *          $cli->send(str_repeat('A', 100)."\n");
     *          sleep(1);
     *    });
     */
    private function onMessage(\swoole_client $client, $data){
        // 1. 接收消息 -- message
        // 2. 处理回调
        // 3. 发送fin
        // 4. 告知ready

        $responseMessageFormat = ResponseNsq::readFormat($this->socket);
        // 区分不同 读取消息是一直读
        if (ResponseNsq::isHeartBeat($responseMessageFormat)) {
            // 如果是心跳 就继续
            $this->socket->send(NsqMessage::nop());
        } else if(ResponseNsq::isMessage($responseMessageFormat)){
            $receiveMsg = new ResponseMessage($responseMessageFormat);
            if (!is_callable($this->callback)) {
                throw new NsqException("Subscribe callback is not callable");
            }

            try {
                // 定义好的回调参数
                call_user_func($this->callback,$this->socket,$receiveMsg);
            }catch (\Exception $e){
                // 消息处理失败

                // 告知重新放入队列
                $this->socket->send(NsqMessage::req($receiveMsg->getId(),3));

                $this->socket->send(NsqMessage::rdy(1));
                Logger::ins()->alert("Deal Message failed ");
                throw new NsqException("Deal Message failed ");
            }


            $this->socket->send(NsqMessage::fin($receiveMsg->getId()));

            $this->socket->send(NsqMessage::rdy(1));

        } else if (ResponseNsq::isOk($responseMessageFormat)){
            // 不做处理
        } else {
            throw new NetworkSocketException("Error frame type from received.");
        }

    }

    /**
     * 处理错误信息
     */
    private function onError(){
        throw new NetworkSocketException("Failed to connect to ".$this->getDomain());
    }

    /**
     * 关闭连接
     */
    private function onClose(){
        //$this->getSocket(true);

        $this->socket->connect($this->host,$this->port,$this->timeout);
    }


}
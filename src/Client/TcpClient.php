<?php
namespace nsqphp\Client;
use nsqphp\Exception\NetworkSocketException;
use nsqphp\Logger\Logger;

/**
 * 使用 Stream 实现消息体的发送和接收
 *
 * @version  : 1.0.0
 * @datetime : 2019/3/20 08:31 08
 */
class TcpClient  extends AbstractProxyClient   {

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
     * 使用TCP 读取消息
     *  本质 : 使用 stream_select  建立读连接
     *             stream_socket_recvfrom 读取数据
     * @param int $length 待读取的长度
     * @return string
     */
    public function read(int $length = 0):string {
        $this->getSocket();
        $read        = [ $this->socket ];
        $surplusLen  = $length;
        $readStr     = $buffer = "";
        $null        = null;
        $readTimeout = 3;
        while (strlen($readStr) < $length) {
            $isReadAble = stream_select($read, $null, $null, $readTimeout,3);
            if ($isReadAble > 0) {
                $buffer = @stream_socket_recvfrom($this->socket, $surplusLen);
                if ($buffer === false) {
                    throw new NetworkSocketException("Cannot read from " . $this->getDomain());
                } else if ($buffer == ''){
                    throw new NetworkSocketException("Read o0 Bytes from " . $this->getDomain());
                } else {
                    // 每次读取的数据
                    $readStr .= $buffer;
                    // 还需要读取的数据总长
                    $surplusLen -= strlen($buffer);
                }
            } else if ($isReadAble === 0){ // 超时
                Logger::ins()->error("Reading Timeout ",[
                    'domain'  => $this->getDomain(),
                    'Timeout' => $readTimeout,
                    'msg'     => $readStr,
                ]);
                throw new NetworkSocketException("Writing Timeout " . $this->getDomain());
            } else { // 失败
                Logger::ins()->error("Reading failed ",[
                    'domain'  => $this->getDomain(),
                    'Timeout' => $readTimeout,
                    'msg'     => $readStr,
                ]);
                throw new NetworkSocketException("Reading failed " . $this->getDomain());
            }
        }

        return $readStr;
    }

    /**
     * 使用TCP 写入消息
     *
     * @param string $buffer
     */
    public function write(string $buffer) {
        // 使用 stream 实现 tcp
        $this->getSocket();
        $write = [ $this->socket ];
        $null  = NULL;
        while (strlen($buffer) > 0) {
            // 等待写
            $writeAble = stream_select($null, $write, $null, $tv_sec);
            if ($writeAble > 0) {
                $wroteLen = stream_socket_sendto($this->socket, $buffer);
                // 已经写的长度
                if ($wroteLen === -1 || $wroteLen === false) {
                    throw new NetworkSocketException("Writing failed [{$this->host}:{$this->port}](1)");
                }
                // 剩下长度
                $buffer = substr($buffer, $wroteLen);
            } else if ($writeAble === 0) { // 超时
                throw new NetworkSocketException("Writing Timeout " . $this->getDomain());
            } else { // 失败
                throw new NetworkSocketException("Writing failed " . $this->getDomain());
            }
        }
    }

    public function reconnect() {

    }

    /**
     * 获取连接
     *
     */
    private function getSocket(){
        // 实现方式不一
        if (!$this->socket) {
            $timeout = 30;
            $this->socket = fsockopen($this->host,$this->port,$errNo,$errMsg,$timeout);
            if (!$this->socket) {
                throw new NetworkSocketException("Connecting failed ".$this->getDomain()." errorNo: ".$errNo." errorMsg:".$errMsg);
            }
            stream_set_blocking($this->socket, 1);
        }
    }
}
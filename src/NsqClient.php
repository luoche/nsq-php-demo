<?php

namespace nsqphp;

use nsqphp\Client\AbstractProxyClient;
use nsqphp\Client\HttpClient;
use nsqphp\Client\TcpClient;
use nsqphp\Exception\NetworkSocketException;
use nsqphp\Exception\NsqException;
use nsqphp\Logger\Logger;
use nsqphp\Server\SwooleServer;
use nsqphp\Server\TcpServer;
use nsqphp\Util\Lookup;
use nsqphp\Util\NsqHttpMessage;
use nsqphp\Util\NsqMessage;
use nsqphp\Util\TcpResponseParse;

/**
 * 本demo 的入口,主要实现
 * 1. 作为生产者 发送消息
 * 2. 作为消费者 订阅消息
 *
 * @version  : 1.0.0
 * @datetime : 2019/3/20 08:08 08
 */
class NsqClient {

    /**
     * 模拟发送数据的客户端
     *
     * @var AbstractProxyClient
     */
    private $proxyClient;

    /**
     * 对应 nsqd 节点的配置信息
     *
     * @var array
     */
    private $nsqdConf;

    /**
     * 订阅的连接池
     *
     * @var array
     */
    private $subPool = [];

    /**
     * 生产者 tcp 的连接池
     *
     * @var array
     */
    private $producerTcpPool = [];

    /**
     * 生产者 HTTP 的连接池
     *
     * @var array
     */
    private $producerHttpPool = [];


    private function initProducerPool(array $nsqConfArr){
        if (empty($nsqConfArr)) {
            Logger::ins()->error("Empty nsqd");
            throw new NsqException("Fail to get nsqd");
        }

        foreach ($nsqConfArr as $index => $nsqConf) {

        }
    }

    /**
     * 建立连接
     *
     * @param array $nsqConf
     */
    public function publishTo(array $nsqConf) {

        $this->initProducerPool($nsqConf);
        // 本质就是实例化不同的Client
        // 先写简单的 一次只是发送到一个nsqd 节点上
        $nsqdConf = $this->_getNsqd($nsqConf);


        if (empty($nsqConf)) {
            throw new NsqException("Failed to get Nsqd node");
        }
        $this->nsqdConf = $nsqdConf;

        if ($this->proxyClient['port'] == 4151) { // HTTP 请求
            // step1 : 先使用Http 模拟所有的请求 是否有连接不上的异常?
            $proxyClient = new HttpClient($nsqdConf['host'],$nsqdConf['port']);
        } else {
            // 使用 TCP 或者 swoole
            $proxyClient = new TcpClient($nsqdConf['host'],$nsqdConf['port']);
        }

        $this->proxyClient = $proxyClient;
    }

    /**
     * 发布消息
     *
     * @param String $topic
     * @param String|array $message 一条或者多条记录
     */
    public function publish(String $topic, $message) {
        // 如果是Http请求
        // 下一次个消息来的时候,统一需要实例化。 增加了实例化的次数 增加了内存的消耗
        if ($this->proxyClient instanceof HttpClient) {
            $this->publishViaHttp($topic, $message);
        } else {
            $this->publishViaTcp($topic, $message);
        }
    }

    /**
     * 通过 Http 的方式发布信息
     *
     * @param String $topic
     * @param String|array $message
     * @throws NetworkSocketException
     * @return bool
     */
    private function publishViaHttp(String $topic, $message){
        // 拼装消息体
        if (is_array($message)) {
            $formatMessage = NsqHttpMessage::mpub($message);
            $url           = NsqHttpMessage::mpubUrl($topic);
        } else {
            $formatMessage = NsqHttpMessage::pub($message);
            $url           = NsqHttpMessage::pubUrl($topic);
        }
        $this->proxyClient->setUrl($url);

        list($error,$res) = $this->proxyClient->write($formatMessage);

        if ($error) { // 发送错误
            list($errNo,$errMsg) = $error;
            Logger::ins()->error("HTTP Publish is failed",[
                "errNo"  => $errNo,
                "errMsg" => $errMsg,
            ]);

            throw new NetworkSocketException("HTTP Publish is failed");
        }
        // 解析模板
        $returnFlag = $res === 'OK';

        return $returnFlag;
    }

    /**
     * 通过 TCP 的方式发布信息
     *
     * @param String $topic
     * @param String|array $message
     * @throws NetworkSocketException
     * @return bool
     */
    private function publishViaTcp(String $topic, $message){
        // 封装 message 后续可以封装成为一个方法,因为消息的多样性
        try {
            // todo 命令规范
            if (is_array($message)) {
                // pub 是一个动词 不能用来封装字符串 做些非动作 mark
                $formatMessage = NsqMessage::mpub($topic,$message);
            } else {
                $formatMessage = NsqMessage::pub($topic,$message);
            }
            // 发送消息
            $this->proxyClient->write($formatMessage);

            // 处理响应
            $nsqResFormatArr = TcpResponseParse::readFormat($this->proxyClient);

            // 排除心跳
            $isHeartBeat = TcpResponseParse::isHeartBeat($nsqResFormatArr);
            while($isHeartBeat){
                $this->proxyClient->write(NsqMessage::nop());

                $nsqResFormatArr = TcpResponseParse::readFormat($this->proxyClient);
            }

            $isPubSuccess = TcpResponseParse::isOk($nsqResFormatArr);
            if (!$isPubSuccess) {
                Logger::ins()->error("failed to send message",[
                    'domain'  => $this->proxyClient->getDomain(),
                    'message' => $message
                ]);
                return false;
            }

        } catch (\Exception $e) {
            // 发送失败的 处理
            $this->proxyClient->reconnect();
        }

        return true;
    }

    /**
     * 订阅一个频道
     *
     * @param array $lookupConf
     * @param string $topic
     * @param string $channel
     * @param string $callback
     * @return NsqClient
     */
    public function subscribe(array $lookupConf,string $topic,string $channel,$callback) {
        if (!is_callable($callback)) {
            throw new NsqException("Callback is invalid; Need a PHP callable");
        }

        // 1. 通过 lookupConf 找到所有的host
        //记录日志
        $lookup = new Lookup($lookupConf);
        $nsqdArr = $lookup->getNode($topic);
        if (empty($nsqdArr)) {
            throw new NsqException("There is no available nsqd for this topic ".$topic);
        }
        Logger::ins()->info("Found the host connect to you nsqd topic".$topic);

        // 遍历所有节点
        foreach ($nsqdArr as $ko => $nsqdConf) {
            // conn 是服务启动在单个节点上,所以需要 连接池
            // 所谓的长连接 就是用完不关闭。 时间很长
            // 建立连接 使用 Stream 或者 swoole
            $conn = new SwooleServer($nsqdConf['host'],$nsqdArr['port']);

            $conn->setParams($topic, $channel, $callback);

            Logger::ins()->info("Connent to you nsqd".$conn->getDomain());

            // 所有的信息 放到 此步完成
            $conn->getSocket();



            // 使用 TCP的方式
            $conn = new TcpServer($nsqdConf['host'],$nsqdArr['port']);
            // 设置 topic 参数
            $conn->setParams($topic, $channel, $callback);
            $conn->getSocket();
            Logger::ins()->info("Connent to you nsqd".$conn->getDomain());
            $conn->dispatchFrame();

            $this->subPool[] = $conn;
        }

        return $this;
    }

    /**
     * TCP 应该只处理TCP的问题,不关心数据 和协议 业务
     *
     * @param $messageId
     */
    public function finish($messageId) {

    }

    /**
     * 从 nsqd 的集群中选择出合适的 Nsqd node
     *
     * @param array $nsqConf
     * @return array
     */
    private function _getNsqd(array $nsqConf):array {
        // 根据配置,随机获取一个Nsqd 节点
        // 先实现发布到一个Nsqd 节点,后续再来实现 2个,多个 todo

        $tempNsqd = shuffle($nsqConf);

        return $tempNsqd[0];
    }
}
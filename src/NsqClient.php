<?php

namespace nsqphp;

use nsqphp\Client\AbstractProxyClient;
use nsqphp\Client\HttpClient;
use nsqphp\Client\TcpClient;
use nsqphp\Exception\NetworkSocketException;
use nsqphp\Exception\NsqException;
use nsqphp\Logger\Logger;
use nsqphp\Util\NsqHttpMessage;
use nsqphp\Util\NsqMessage;
use nsqphp\Util\ResponseNsq;

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

    public function publishTo(array $nsqConf) {
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
     * @param String $message
     */
    public function publish(String $topic,String $message) {
        // 如果是Http请求
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
     * @param String $message
     * @throws NetworkSocketException
     * @return bool
     */
    private function publishViaHttp(String $topic,String $message){
        // 拼装消息体
        $formatMessage = NsqHttpMessage::pub($message);
        $url           = NsqHttpMessage::pubUrl($topic);
        $proxyClient   = new HttpClient($this->nsqdConf['host'],$this->nsqdConf['port']);
        $proxyClient->setUrl($url);

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
     * @param String $message
     * @throws NetworkSocketException
     * @return bool
     */
    private function publishViaTcp(String $topic,String $message){
        // todo
        // 封装 message 后续可以封装成为一个方法,因为消息的多样性
        try {
            $formatMessage = NsqMessage::pub($topic,$message);
            // 发送消息
            $this->proxyClient->write($formatMessage);

            // 处理响应
            $nsqRes = $this->proxyClient->read();

            $nsqResFormatArr = ResponseNsq::readFormat($nsqRes);

            // 排除心跳
            $isHeartBeat = ResponseNsq::isHeartBeat($nsqResFormatArr);
            while($isHeartBeat){
                $nsqRes = $this->proxyClient->read();
                $nsqResFormatArr = ResponseNsq::readFormat($nsqRes);
            }

            $isPubSuccess = ResponseNsq::isOk($nsqResFormatArr);
            if (!$isPubSuccess) {
                Logger::ins()->error("failed to send message",[
                    'domain'  => $this->proxyClient->getDomain(),
                    'message' => $message
                ]);
            }

        } catch (\Exception $e) {
            // 发送失败的 处理
            $this->proxyClient->reconnect();
        }
    }

    public function subscribe() {
        // todo
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
        if (empty($nsqConf)) {
            Logger::ins()->error("Empty nsqd");
            throw new NsqException("Fail to get nsqd");
        }
        $tempNsqd = shuffle($nsqConf);

        return $tempNsqd[0];
    }
}
<?php
namespace nsqphp;
use nsqphp\Client\AbstractProxyClient;
use nsqphp\Client\HttpClient;
use nsqphp\Exception\PublishException;
use nsqphp\Logger\Logger;
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

    public function publishTo(array $nsqConf) {
        // 本质就是实例化不同的Client
        // 先写简单的 一次只是发送到一个nsqd 节点上
        $nsqdConf = $this->_getNsqd($nsqConf);
        if (empty($nsqConf)) {
            throw new PublishException("failed to get Nsqd node");
        }

        // step1 : 先使用Http 模拟所有的请求 是否有连接不上的异常?
        $proxyClient = new HttpClient($nsqdConf['host'],$nsqdConf['port']);

        $this->proxyClient = $proxyClient;
    }

    public function publish(String $topic,String $message) {
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

    }

    private function _getNsqd(array $nsqConf):array {
        // 根据配置,随机获取一个Nsqd 节点
        // 先实现发布到一个Nsqd 节点,后续再来实现 2个,多个 todo

        return [
            'host' => '192.168.1.50', 'port' => 4151
        ];
    }
}
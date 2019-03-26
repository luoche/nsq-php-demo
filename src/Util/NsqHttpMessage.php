<?php
namespace nsqphp\Util;

/**
 * NsqHttpMessage
 *  发送NSQ 二进制消息体 针对消息的打包处理
 *
 * @version  : 1.0.0
 * @datetime : 2019/3/20 08:14 08
 */
class NsqHttpMessage {

    const PUB = "pub";

    const MPUB = "mpub";

    /**
     * 发送消息体
     *  curl -d "<message>" http://127.0.0.1:4151/pub?topic=message_topic`
     *
     * @param string $message
     * @return string
     */
    public static function pub(string $message):string {
        // pub 的消息体
        return self::packet($message);
    }

    /**
     * 发送多条消息体
     *  curl -d "<message>\n<message>\n<message>" http://127.0.0.1:4151/mpub?topic=message_topic`
     *  或者 ?binary=true 查询参数来允许二进制模式
     *   [ 4-byte num messages ]
     *   [ 4-byte message #1 size ][ N-byte binary data ]
     *   ... (repeated <num_messages> times)
     *
     * @param array $messageArr
     * @return string
     */
    public static function mpub(array $messageArr):string {
        // pub 的消息体
        $msgs = "";
        foreach ($messageArr as $index => $message) {
            $msgData = self::packet($message); //[ N-byte binary data ]
            $msgSize = pack("N",strlen($msgData)); // [ 4-byte message #1 size ]
            $msgs   .= $msgSize.$msgData; //  [ 4-byte message #1 size ][ N-byte binary data ]
        }
        $msgsCount = pack("N",count($messageArr)); //[ 4-byte num messages ]
        return $msgsCount.$msgs;
    }

    /**
     * 获取发布地址的URL
     *
     * @param string $topic
     * @return string
     */
    public static function pubUrl(string $topic):string {
        return sprintf('pub?topic=%s', $topic);
    }

    /**
     * 获取发送多条消息发布地址的URL
     *
     * @param string $topic
     * @return string
     */
    public static function mpubUrl(string $topic):string {
        return sprintf('mpub?topic=%s&binary=true', $topic);
    }

    /**
     * 信息打包
     *
     * @param null   $message   发送的信息
     * @return string
     */
    public static function packet($message = null) {
        $out = '';
        $len = strlen($message);
        for ($i = 0; $i < $len; $i++){
            $out .= pack('c', ord(substr($message, $i, 1)));
        }
        return $out;
    }
}
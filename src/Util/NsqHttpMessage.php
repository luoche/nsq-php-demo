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

    const PUB = "PUB";
    /**
     * 发送消息体
     *
     * @param string $message
     * @return string
     */
    public static function pub(string $message):string {
        // pub 的消息体
        return self::packet($message);
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
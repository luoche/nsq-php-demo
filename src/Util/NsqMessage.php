<?php
namespace nsqphp\Util;

/**
 * Class 类名
 *  结合 nsq 的消息体,通过text 生产对应的消息体
 *
 * @version  : 1.0.0
 * @datetime : 2019/3/20 08:14 08
 */
class NsqMessage {

    const PUB = "PUB";
    /**
     * 发送消息体
     *
     *  PUB <topic_name>\n
     *  [ 4-byte size in bytes ][ N-byte binary data ]
     *  <topic_name> - a valid string (optionally having #ephemeral suffix)
     *
     *
     * @param string $topic
     * @param string $message
     * @return string
     */
    public static function pub(string $topic,string $message):string {
        // pub 的消息体

        return self::packet(self::PUB,$topic,$message);
    }

    public static function nop():string  {

    }

    public static function message() {

    }

    public static function fin() {

    }

    public static function rdy() {

    }

    /**
     * 信息打包
     *
     * @param string $single    打包的命令
     * @param null   $param     topic 信息
     * @param null   $message   发送的信息
     * @return string
     */
    public static function packet(string $single, $param = null,$message = null) {
        //PUB <topic_name>\n
        //  [ 4-byte size in bytes ][ N-byte binary data ]
        //  <topic_name> - a valid string (optionally having #ephemeral suffix)

        if ($message != null) {
            $message = pack("N",strlen($message)).$message;
        }

        return sprintf("%s %s\n%s",$single,$param,$message);
    }
}
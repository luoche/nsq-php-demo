<?php
namespace nsqphp\Util;

/**
 * NsqMessage
 *  发送NSQ 二进制消息体 针对消息的打包处理
 *
 * @version  : 1.0.0
 * @datetime : 2019/3/20 08:14 08
 */
class NsqMessage {

    const MAGIC_V2 = "  V2";

    const IDENTIFY = "IDENTIFY";
    const PING = "PING";
    const SUB = "SUB";
    const PUB = "PUB";
    const MPUB = "MPUB";
    const RDY = "RDY";
    const FIN = "FIN";
    const REQ = "REQ";
    const TOUCH = "TOUCH";
    const CLS = "CLS";
    const NOP = "NOP";
    const AUTH = "AUTH";

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

    /**
     * 发送多条消息体
     *
     *  MPUB <topic_name>\n
     *  [ 4-byte body size ]
     *  [ 4-byte num messages ]
     *  [ 4-byte message #1 size ][ N-byte binary data ]
     *  ... (repeated <num_messages> times)
     *
     *  <topic_name> - 字符串 (建议 having #ephemeral suffix)
     *
     *
     * @param string $topic
     * @param array $messageArr
     * @return string
     */
    public static function mpub(string $topic,array $messageArr):string {
        // mpub 的消息体
        $msgs = "";
        foreach ($messageArr as $index => $message) {
            $msgs .= pack("N",strlen($message)).$message; // [ 4-byte message #1 size ][ N-byte binary data ]
        }
        $msgSize  = pack("N",strlen($msgs));    // [ 4-byte body size ]
        $msgCount = pack("N",count($messageArr)); // [ 4-byte num messages ]

        return sprintf("%s %s\n%s%s%s",self::MPUB,$topic,$msgSize,$msgCount,$msgs);
    }

    /**
     * SUB
     * 订阅话题（topic) /通道（channel)
     *
     * SUB <topic_name> <channel_name>\n
     *
     * <topic_name> - 字符串 (建议包含 #ephemeral 后缀)
     * <channel_name> - 字符串 (建议包含 #ephemeral 后缀)
     *
     * @param string $topic
     * @param string $channel
     * @return string
     */
    public static function sub(string $topic,string $channel):string {
        return self::packet(self::SUB,[$topic,$channel]);
    }

    /**
     * 发送 version identifier
     *
     * @return string
     */
    public static function magic() {
        return self::MAGIC_V2;
    }

    /**
     * RDY
     * 更新 RDY 状态 (表示你已经准备好接收N 消息)
     *
     * 注意: nsqd v0.2.20+ 使用 --max-rdy-count 表示这个值
     *
     * RDY <count>\n
     *
     * <count> - a string representation of integer N where 0 < N <= configured_max
     *
     * @param int $count
     * @return string
     */
    public static function rdy(int $count = 1) {
        return self::packet(self::RDY,$count);
    }

    /**
     * 完成一个消息 (表示成功处理)
     *
     * FIN <message_id>\n
     *
     * <message_id> - message id as 16-byte hex string
     *
     * @param int $messageId
     * @return string
     */
    public static function fin(int $messageId) {
        return self::packet(self::FIN,$messageId);
    }

    /**
     * REQ
     * 重新将消息队列（表示处理失败）
     *
     * 这个消息放在队尾，表示已经发布过，但是因为很多实现细节问题，不要严格信赖这个，将来会改进。
     *
     * 简单来说，消息在传播途中，并且超时就表示 REQ。
     *
     * REQ <message_id> <timeout>\n
     *
     * <message_id> - message id as 16-byte hex string
     * <timeout> - a string representation of integer N where N <= configured max timeout
     * 0 is a special case that will not defer re-queueing
     *
     * @param int $messageId
     * @param int $timeout
     * @return string
     */
    public static function req(int $messageId,int $timeout) {
        // 这二个参数都在 换行之前
        return self::packet(self::REQ,[$messageId,$timeout]);
    }

    /**
     * TOUCH
     * 重置传播途中的消息超时时间
     *
     * 注意: 在 nsqd v0.2.17+ 可用
     *
     * TOUCH <message_id>\n
     *
     * <message_id> - the hex id of the message
     * 注意: 这里没有成功后响应
     *
     * @param int $messageId
     * @return string
     */
    public static function touch(int $messageId):string {
        return self::packet(self::TOUCH,$messageId);
    }

    /**
     * cls 消息
     *  清除连接（不再发送消息）
     *  格式:    CLS\n
     */
    public static function cls() {
        return self::packet(self::CLS);
    }

    /**
     * 发送 NOP 消息体
     *  NOP\n
     *
     * @return string
     */
    public static function nop():string  {
        return self::packet(self::NOP);
    }

    public static function message() {

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

        // 命令之后 换行之前 多个参数信息
        if (is_array($param)) {
            $param = implode(" ", $param);
        }

        if ($message != null) {
            $message = pack("N",strlen($message)).$message;
        }

        return sprintf("%s %s\n%s",$single,$param,$message);
    }
}
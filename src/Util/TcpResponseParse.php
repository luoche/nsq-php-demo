<?php
namespace nsqphp\Util;
use nsqphp\Client\AbstractProxyClient;
use nsqphp\Exception\SocketException;

/**
 * 按照Nsq 的通用返回消息 处理返回消息
 *
 * @version  : 1.0.0
 * @datetime : 2019/3/20 08:14 08
 */
class TcpResponseParse {

    //FrameTypeResponse int32 = 0
    //FrameTypeError    int32 = 1
    //FrameTypeMessage  int32 = 2
    /**
     * 返回TCP格式中的响应体
     */
    const FRAME_TYPE_RESPONSE = 0;
    /**
     * 返回TCP格式中的错误体
     */
    const FRAME_TYPE_ERROR = 1;
    /**
     * 返回TCP格式中的消息体
     */
    const FRAME_TYPE_MESSAGE = 2;

    /**
     * Heartbeat response content
     */
    const HEARTBEAT = '_heartbeat_';

    /**
     * OK response content
     */
    const OK = 'OK';


    /**
     * 解析TCP 并且返回为数组格式的解析体
     *
     * 返回的TCP格式 :
     *          [x][x][x][x][x][x][x][x][x][x][x][x]...
     *          |  (int32) ||  (int32) || (binary)
     *          |  4-byte  ||  4-byte  || N-byte
     *          ------------------------------------...
     *          size     frame type     data
     *
     *
     * @param AbstractProxyClient $conn
     * @return array
     */
    public static function readFormat(AbstractProxyClient $conn):array {
        // 可能返回不同的格式, message 或者
        //$size = $frameType = null;
        // 静态方法 所有的进程公用
        $readTcp   = new ReadTcp($conn);
        try {
            $size      = $readTcp->readInt();
            $frameType = $readTcp->readInt();
        } catch (SocketException $e) {
            throw new SocketException("Error reading message from ".$e->getMessage(),null,$e);
        }

        $frame = [
            'size' => $size,
            'type' => $frameType,
        ];

        //FrameTypeResponse int32 = 0
        //FrameTypeError    int32 = 1
        //FrameTypeMessage  int32 = 2
        // 减去类型长度
        $msgSize = $size-4;
        // 分析返回值
        switch ($frameType) {
            case self::FRAME_TYPE_RESPONSE:
                $response = $readTcp->readString($msgSize);
                $frame['response'] = $response;
                break;
            case self::FRAME_TYPE_ERROR:
                $error = $readTcp->readString($msgSize);
                $frame['error'] = $error;
                break;
            case self::FRAME_TYPE_MESSAGE:

                //[x][x][x][x][x][x][x][x][x][x][x][x][x][x][x][x][x][x][x][x][x][x][x][x][x][x][x][x][x][x]...
                //|       (int64)        ||    ||      (hex string encoded in ASCII)           || (binary)
                //|       8-byte         ||    ||                 16-byte                      || N-byte
                // ------------------------------------------------------------------------------------------...
                //nanosecond timestamp    ^^                   message ID                       message body
                //                     (uint16)
                //                        2-byte
                //                    attempts

                // 建立一个消息类更好 ResponseMessage
                $frame["timestamp"] = $readTcp->readLong();
                $frame["attempts"]  = $readTcp->readShort();
                $frame["id"]        = $readTcp->readString(16);
                $frame["message"]   = $readTcp->readString($msgSize - 26);
                break;
            default:
                throw new SocketException("Tcp return unknown frame type: message is ".$readTcp->readString($msgSize));
                break;
        }

        return $frame;
    }

    /**
     * 从已经读取消息中 格式化信息
     *
     * @param string $buffer
     * @return array
     */
    public static function readFormatFromBuffer(string $buffer):array {
        // 可能返回不同的格式, message 或者
        //$size = $frameType = null;
        $tcpRead   = new TcpRead($buffer);
        try {
            $size      = $tcpRead->readInt();
            $frameType = $tcpRead->readInt();
        } catch (SocketException $e) {
            throw new SocketException("Error reading message from ".$e->getMessage(),null,$e);
        }

        $frame = [
            'size' => $size,
            'type' => $frameType,
        ];

        //FrameTypeResponse int32 = 0
        //FrameTypeError    int32 = 1
        //FrameTypeMessage  int32 = 2
        // 减去类型长度
        $msgSize = $size-4;
        // 分析返回值
        switch ($frameType) {
            case self::FRAME_TYPE_RESPONSE:
                $response = $tcpRead->readString($msgSize);
                $frame['response'] = $response;
                break;
            case self::FRAME_TYPE_ERROR:
                $error = $tcpRead->readString($msgSize);
                $frame['error'] = $error;
                break;
            case self::FRAME_TYPE_MESSAGE:

                //[x][x][x][x][x][x][x][x][x][x][x][x][x][x][x][x][x][x][x][x][x][x][x][x][x][x][x][x][x][x]...
                //|       (int64)        ||    ||      (hex string encoded in ASCII)           || (binary)
                //|       8-byte         ||    ||                 16-byte                      || N-byte
                // ------------------------------------------------------------------------------------------...
                //nanosecond timestamp    ^^                   message ID                       message body
                //                     (uint16)
                //                        2-byte
                //                    attempts

                // 建立一个消息类更好 ResponseMessage
                $frame["timestamp"] = $tcpRead->readLong();
                $frame["attempts"]  = $tcpRead->readShort();
                $frame["id"]        = $tcpRead->readString(16);
                $frame["message"]   = $tcpRead->readString($msgSize - 26);
                break;
            default:
                throw new SocketException("Tcp return unknown frame type: message is ".$tcpRead->readString($msgSize));
                break;
        }

        return $frame;
    }

    /**
     * 返回的消息是否是心跳包
     *
     * @param array $reqFrame
     * @return bool
     */
    public static function isHeartBeat(array $reqFrame):bool {
        return self::isResponse($reqFrame) && $reqFrame['response'] == self::HEARTBEAT;
    }

    /**
     * 返回的消息是否是OK
     *
     * @param array $reqFrame
     * @return bool
     */
    public static function isOk(array $reqFrame):bool {
        return self::isResponse($reqFrame) && $reqFrame['response'] == self::OK;
    }

    /**
     * 返回的消息是否是 response 类型。 不过有点重复的意思,因为上面已经做了判断
     *
     * @param array $reqFrame
     * @return bool
     */
    public static function isResponse(array $reqFrame) {
        return isset($reqFrame['type']) && $reqFrame['type'] == self::FRAME_TYPE_RESPONSE;
    }

    /**
     * 返回的消息是否是 error 类型。 不过有点重复的意思,因为上面已经做了判断
     *
     * @param array $reqFrame
     * @return bool
     */
    public static function isError(array $reqFrame) {
        return isset($reqFrame['type']) && $reqFrame['type'] == self::FRAME_TYPE_ERROR;
    }

    /**
     * 返回的消息是否是 message 类型。 不过有点重复的意思,因为上面已经做了判断
     *
     * @param array $reqFrame
     * @return bool
     */
    public static function isMessage(array $reqFrame) {
        return isset($reqFrame['type']) && $reqFrame['type'] == self::FRAME_TYPE_MESSAGE;
    }


}
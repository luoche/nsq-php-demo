<?php
namespace nsqphp\Util;

/**
 * 解析TCP二进制包 (数据包已经接收)
 *  mark 命名应该规范
 *
 * @version  : 1.0.0
 * @datetime : 2019/3/20 08:14 08
 */
class TcpRead {

    /**
     * 消息体
     *
     * @var string $message
     */
    private $message;

    public function __construct(string $message) {
        $this->message = $message;
    }

    /**
     * 从打包的二进制信息中读取 short 类型数据
     *
     * @return int
     */
    public function readShort():int {
        // short 类型 使用 n
        list(,$res) = unpack('n', $this->readChar(2));
        return $res;
    }

    /**
     * 从打包的二进制信息中读取 int 类型数据
     *
     * @return int
     */
    public function readInt():int  {
        // int 类型 使用 N
        list(,$res) = unpack('N', $this->readChar(4));
        if ((PHP_INT_SIZE !== 4)) {
            $res = sprintf("%u", $res);
        }
        return (int)$res;
    }

    /**
     * 从打包的二进制信息中读取long类型数据
     *
     * @return int
     */
    public function readLong():int {
        $high  = unpack('N', $this->readChar(4));
        $lower = unpack('N', $this->readChar(4));

        // workaround signed/unsigned braindamage in php
        $high  = sprintf("%u", $high[1]);
        $lower = sprintf("%u", $lower[1]);

        return bcadd(bcmul($high, "4294967296" ), $lower);
    }

    /**
     * 从打包的二进制信息中读取字符串
     *
     * @param int $size
     * @return string
     */
    public function readString($size = 4):string {
        // string 类型 使用 c
        $temp = unpack("c{$size}chars", $this->readChar($size));
        $out = "";
        foreach($temp as $v) {
            if ($v > 0) {
                $out .= chr($v);
            }
        }
        return $out;
    }

    /**
     * 读取固定长度的字符串,并且截取之前的字符串
     *
     * @param int $length
     * @return string
     */
    public function readChar(int $length) {
        $charMessage = substr($this->message,0,$length);

        // 更新message的值
        $this->message = substr($this->message,$length);
        return $charMessage;
    }
}
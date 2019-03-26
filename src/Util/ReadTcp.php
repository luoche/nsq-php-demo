<?php
namespace nsqphp\Util;
use nsqphp\Client\AbstractProxyClient;

/**
 * 解析TCP二进制包---可以合并到上一个--tcpReader
 *
 * @version  : 1.0.0
 * @datetime : 2019/3/20 08:14 08
 */
class ReadTcp {

    /**
     * 建立的连接
     *
     * @var AbstractProxyClient
     */
    private $conn;

    public function __construct(AbstractProxyClient $conn) {
        $this->conn = $conn;
    }

    /**
     * 从打包的二进制信息中读取 short 类型数据
     *
     * @return int
     */
    public function readShort():int {
        // short 类型 使用 n
        list(,$res) = unpack('n', $this->conn->read(2));
        return $res;
    }

    /**
     * 从打包的二进制信息中读取 int 类型数据
     *
     * @return int
     */
    public function readInt():int  {
        // int 类型 使用 N
        list(,$res) = unpack('N', $this->conn->read(4));
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
        $high  = unpack('N', $this->conn->read(4));
        $lower = unpack('N', $this->conn->read(4));

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
        $temp = unpack("c{$size}chars", $this->conn->read($size));
        $out = "";
        foreach($temp as $v) {
            if ($v > 0) {
                $out .= chr($v);
            }
        }
        return $out;
    }
}
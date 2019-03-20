<?php
namespace nsqphp\Util;

/**
 * 按照Nsq 的通用返回消息 处理返回消息
 *
 * @version  : 1.0.0
 * @datetime : 2019/3/20 08:14 08
 */
class ResponseNsq {

    public static function readFormat(string $reqStr):array {
        // 可能返回不同的格式, message 或者
        return [];
    }

    public static function isHeartBeat(array $reqFrame):bool {
        // todo
        return true;
    }

    public static function isOk(array $reqFrame):bool {
        // todo
        return true;
    }
}
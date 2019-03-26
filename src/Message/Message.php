<?php
namespace nsqphp\Message;
use nsqphp\Exception\NsqException;

/**
 *  把订阅消息 读取的消息 封装为一个 消息体(Message)类
 *
 * @version  : 1.0.0
 * @datetime : 2019/3/20 08:14 08
 */
class Message {

    /**
     * @var int
     */
    private $id = null;

    /**
     * @var string
     */
    private $payload = '';

    /**
     * attempts
     *
     * @var int
     */
    private $attempts = 0;

    /**
     * @var float
     */
    private $ts;


    public function __construct(array $frame) {
        if (!isset($frame['payload']) || !isset($frame['id']) || !isset($frame['attempts']) || !isset($frame['ts'])) {
            throw new NsqException('Error message frame');
        }

        $this->payload  = $frame['payload'];
        $this->id       = $frame['id'];
        $this->attempts = $frame['attempts'];
        $this->ts       = $frame['ts'];
    }

    public function getId() {
        return $this->id;
    }

    public function getPayload() {
        return $this->payload;
    }

    public function getAttempts() {
        return $this->attempts;
    }

    public function getTs() {
        return $this->ts;
    }

}
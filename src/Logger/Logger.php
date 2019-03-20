<?php

namespace nsqphp\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\NullLogger;

/**
 * Class Logger
 *
 * @version  : 1.0.0
 * @package  nsqphp\Logger
 */
class Logger extends AbstractLogger
{
    /**
     * @var self
     */
    private static $instance = null;

    /**
     * @var NullLogger
     */
    private $nullLogger = null;

    /**
     * @return self
     */
    public static function ins()
    {
        if (is_null(self::$instance))
        {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Logger constructor.
     */
    public function __construct()
    {
        $this->nullLogger = new NullLogger;
    }

    /**
     * @param mixed $level
     * @param string $message
     * @param array $context
     */
    public function log($level, $message, array $context = array())
    {
        $this->nullLogger->log($level, $message, $context);
    }
}
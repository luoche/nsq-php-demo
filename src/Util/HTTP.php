<?php

namespace nsqphp\Util;

/**
 * Class HTTP
 *
 * @version  : 1.0.0
 * @datetime : ${date} ${HOUR}
 * @package  nsqphp\Util
 */
class HTTP {

    /**
     * @var array
     */
    private static $headers = ['Accept: application/vnd.nsq; version=1.0'];

    /**
     * @var string
     */
    private static $encoding = '';

    /**
     * @param $url
     * @param array $extOptions
     * @return array
     */
    public static function get($url, $extOptions = []) {
        return self::request($url, [], $extOptions);
    }

    /**
     * @param $url
     * @param $data
     * @param array $extOptions
     * @return array
     */
    public static function post($url, $data, $extOptions = []) {
        return self::request($url, [CURLOPT_POST => TRUE, CURLOPT_POSTFIELDS => $data], $extOptions);
    }

    /**
     * @param $url
     * @param $selfOptions
     * @param $usrOptions
     * @return array
     */
    private static function request($url, $selfOptions, $usrOptions) {
        $ch = curl_init();

        $initOptions = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HEADER         => FALSE,
            CURLOPT_FOLLOWLOCATION => FALSE,
            CURLOPT_ENCODING       => self::$encoding,
            CURLOPT_USERAGENT      => "nsq-php-demo",
            CURLOPT_HTTPHEADER     => self::$headers,
            CURLOPT_FAILONERROR    => TRUE
        ];

        $selfOptions && $initOptions = self::mergeOptions($initOptions, $selfOptions);
        $usrOptions  && $initOptions = self::mergeOptions($initOptions,  $usrOptions);

        curl_setopt_array($ch, $initOptions);

        $result = curl_exec($ch);

        $error = curl_errno($ch) ? [curl_errno($ch), curl_error($ch)] : null;

        curl_close($ch);

        return [$error, $result];
    }

    /**
     * @param $base
     * @param $custom
     * @return mixed
     */
    private static function mergeOptions($base, $custom) {
        foreach ($custom as $key => $val) {
            $base[$key] = $val;
        }
        return $base;
    }
}
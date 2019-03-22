<?php
namespace nsqphp\Util;
use nsqphp\Exception\LookupException;

/**
 * nsqd 的 lookup 类方法
 *
 * @version  : 1.0.0
 * @datetime : 2019/3/20 08:14 08
 */
class Lookup {

    /**
     * lookup 地址的 host
     *
     * @var string
     */
    private $host;
    /**
     * lookup 地址的 端口
     *
     * @var string
     */
    private $port = 4161;

    /**
     * Lookup constructor.
     *
     * @param array $lookupConf 查询 lookup 的 host 和 port
     */
    public function __construct(array $lookupConf) {
        if (empty($lookupConf)) {
            throw new LookupException("Lookup address is null");
        }

        if (!isset($lookupConf['host'])) {
            throw new LookupException("Lookup address is not format. you maybe lose you host ");
        }

        $this->host = $lookupConf['host'];
        $this->port = isset($lookupConf['port']) ? $lookupConf['port'] : $this->port;
    }

    /**
     * 根据lookup查找对应的nsqd 节点
     *  eg : http://192.168.1.51:4161/lookup?topic=web_admin_export
     *
     * @param string $topic
     * @return array
     *  $return = [
     *         ["host" => "192.168.1.51","port"=>4151 ],
     *         ["host" => "192.168.1.50","port"=>4151 ],
     *
     *  ];
     */
    public function getNode($topic):array {
        $lookupUrl = "http://".$this->host.":".$this->port."/lookup?topic=" . urlencode($topic);

        $res    = HTTP::get($lookupUrl);
        $resArr = json_decode($res, true);

        $parseData = $this->parseNsqLookup($resArr);
        return $parseData;
    }

    /**
     * 从查询的节点信息 格式化nsqd
     *
     * producers :
     *  [
     *         {
     *            "remote_address": "192.168.1.51:49838",
     *             "hostname": "dev-51",
     *             "broadcast_address": "192.168.1.51",
     *             "tcp_port": 4150,
     *             "http_port": 4151,
     *             "version": "0.3.8"
     *         },
     *         {
     *             "remote_address": "192.168.1.50:60568",
     *             "hostname": "dev-50",
     *             "broadcast_address": "192.168.1.50",
     *             "tcp_port": 4150,
     *             "http_port": 4151,
     *             "version": "0.3.8"
     *         }
     *  ]
     *
     * @param array $resArr
     * @return array
     */
    private function parseNsqLookup(array $resArr):array {
        $nsqdProducers = [];
        if (isset($resArr['data'],$resArr['data']['producers']) ) {
            $nsqdProducers = $resArr['data']['producers'];
        }
        if (empty($nsqdProducers)) {
            throw new LookupException("Nsqd is null");
        }

        $nsqdConfArr = [];
        foreach ($nsqdProducers as $index => $producer) {
            $host = isset($producer['address']) ? $producer['address'] : $producer['broadcast_address'];
            $port = $producer['tcp_port'];

            // 为了排重
            $nsqdConfArr[$host] = [
                'host' => $host,
                'port' => $port,
            ];
        }

        return array_values($nsqdConfArr);
    }

}
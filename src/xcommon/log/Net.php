<?php

namespace Xwork\xcommon\log;

use Xwork\xcommon\DBC;
use GuzzleHttp\Client;

/**
 * 日志发送到远程
 * Class Net
 * @package Xwork\xcommon\log
 */
class Net implements ILogger
{
    private $host = '';
    private $timeout = 10;

    public function __construct($host, $timeout=0) {
        DBC::requireNotEmpty($host, __METHOD__ . ' host cant not be empty');
        $this->host = $host;
        if ($timeout > 0) {
            $this->timeout = $timeout;
        }
    }

    public function save($logstr, $logtype) {
        // TODO: Implement save() method.
        $client = new Client();
        $client->request('POST', $this->host, [
            'form_params' => [
                'logstr' => $logstr,
                'logtype' => $logtype,
            ],
            'timeout' => $this->timeout,
        ]);
    }
}
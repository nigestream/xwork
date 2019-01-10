<?php

namespace Xwork\xcommon\log;
class File implements ILogger
{
    private $logPath = '/tmp/xworklog';
    public function __construct($logPath = '') {
        if (!empty($logPath)) {
            $this->logPath = $logPath;
        }
    }

    public function save($logstr, $logtype) {
        $fileName = $this->getFileName($logtype);
        $this->writeToFile($logstr, $fileName);
    }

    private function getFileName($logtype) {
        if ($logtype == 1) {
            $filename = date("Ymd", time()) . ".log";
        } else {
            $filename = date("Ymd", time()) . ".error.log";
        }
        return $this->logPath . DIRECTORY_SEPARATOR . $filename;
    }

    //真正的写磁盘
    private function writeToFile($str, $filename) {
        if (empty($str)) {
            return false;
        }

        if (empty($filename)) {
            return false;
        }

        $logpath = dirname($filename);
        if (!is_dir($logpath)) {
            mkdir($logpath, 0777);
        }

        if (!file_exists($filename)) {
            touch($filename);
            chmod($filename, 0644);
        }

        file_put_contents($filename, $str, FILE_APPEND | LOCK_EX);
        return false;
    }
}
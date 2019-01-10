<?php

namespace Xwork;

use Xwork\xcommon\Config;
use Xwork\xcommon\log\File;
use Xwork\xcommon\log\Log;
use Xwork\xmap\DaoBase;

class TheSystem
{
    protected static $the_system_log = [];

    // 系统初始化函数
    public static function init($configFile, $entryFile = '', $ob_start_callback_flushxworklog = false) {
        Config::setConfigFile($configFile);
        Config::setConfig("needUrlRewrite", true);
        DaoBase::initDefaultDb(Config::getConfig('database')['default_db']);
        self::init_ini_set();
        self::initLog($entryFile);
        self::initErrorHandler();

        if ($ob_start_callback_flushxworklog) {
            ob_start(function ($buffer) {
                Log::sys("[-- callback_flushxworklog --]");
                // 页面请求
                if (getenv('HTTP_HOST') !== false) {
                    Log::flushXworklog();
                }

                return $buffer;
            });
        }
    }

    // 初始化php环境变量
    public static function init_ini_set() {
        ini_set("arg_seperator.output", "&amp;");
        ini_set("magic_quotes_gpc", 0);
        ini_set("magic_quotes_sybase", 0);
        ini_set("magic_quotes_runtime", 0);
        mb_internal_encoding("UTF-8");
        date_default_timezone_set('PRC');
    }

    // 初始化debug参数
    public static function initLog($entryFile) {
        //默认使用文件保存日志
        $fileLogger = new File(Config::getConfig('logpath'));
        Log::addLogger($fileLogger);
        Log::initUnitofworkId();
        $arr = explode("/", $entryFile);
        $fileName = array_pop($arr);
        if ($fileName == 'index.php') {
            $fileName = '';
        }
        Log::setCronName($fileName);

    }

    // 设置系统出错处理钩子
    public static function initErrorHandler() {
        register_shutdown_function(function () {
            if ($e = error_get_last()) {
                if ($e['type'] == E_ERROR || $e['type'] == E_WARNING) {
                    static $errorDesc = array(
                        E_ERROR => 'FATAL_ERROR',
                        E_WARNING => 'WARNING');
                    $type = $errorDesc[$e['type']];
                    self::$the_system_log[$type][] = $type . ': ' . $e['message'] . " in " . $e['file'] . ' line ' . $e['line'];
                }
            }
            if (!self::$the_system_log || !is_array(self::$the_system_log)) {
                return;
            }
            ksort(self::$the_system_log); // 始终让ERROR 信息先记录，为了报警
            foreach (self::$the_system_log as $type => $one) {
                if ($type == 'FATAL_ERROR') {
                    foreach ($one as $a) {
                        Log::error($a);
                    }

                    // 如果是定时脚本,通知并记 xworklog 日志
                    if (Log::getCronName()) {
                        Log::flushXworklog();
                    }
                } else {
                    foreach ($one as $a) {
                        Log::warn($a);
                    }
                }
            }
        });
        set_error_handler(function ($error, $message, $file, $line) {
            switch ($error) {
                case E_ERROR:
                    $type = 'FATAL_ERROR';
                    break;
                case E_WARNING:
                    $type = 'WARNING';
                    break;
                case E_NOTICE:
                    $type = 'NOTICE';
                    break;
                default:
                    $type = 'Unknown error type [' . $error . ']';
                    break;
            }
            $log = $type . ': ' . $message . ' in line ' . $line . ' of file ' . $file . ', PHP ' . PHP_VERSION . ' (' . PHP_OS . ')';
            if (function_exists('debug_backtrace')) {
                $backtrace = debug_backtrace();
                for ($level = 1; $level < count($backtrace); $level++) {
                    $message = 'File: ' . $backtrace[$level]['file'] . ' Line: ' . $backtrace[$level]['line'] . ' Function: ';
                    if (IsSet($backtrace[$level]['class'])) {
                        $message .= '(class ' . $backtrace[$level]['class'] . ') ';
                    }
                    if (IsSet($backtrace[$level]['type'])) {
                        $message .= $backtrace[$level]['type'] . ' ';
                    }
                    $message .= $backtrace[$level]['function'] . '(';
                    if (IsSet($backtrace[$level]['args'])) {
                        for ($argument = 0; $argument < count($backtrace[$level]['args']); $argument++) {
                            if ($argument > 0) {
                                $message .= ', ';
                            }
                            $message .= json_encode($backtrace[$level]['args'][$argument], JSON_UNESCAPED_UNICODE);
                        }
                    }
                    $message .= ')';
                    $log .= " #" . ($level - 1) . ' ' . $message . ' ';
                }
            }
            self::$the_system_log[$type][] = $log;
        }, E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
    }
}




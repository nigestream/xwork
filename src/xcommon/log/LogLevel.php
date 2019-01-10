<?php
namespace Xwork\xcommon\log;
use Xwork\xcommon\Config;

/**
 * Created by PhpStorm.
 * User: nigestream
 * Date: 2018/5/23
 * Time: 18:40
 */

class LogLevel
{
    //trace < sql < sys < [log] < info < warn < error
    const LEVEL_TRACE = 0;
    const LEVEL_INFO = 1;
    const LEVEL_WARN = 2;
    const LEVEL_ERROR = 3;
    const LEVEL_SQL = 20;
    const LEVEL_SYS = 30;
    const LEVEL_XWORK_DEV = 40;

    const LEVEL_UNKNOW = 100;

    private static $levelStrs = [
        self::LEVEL_TRACE => 'TRC',
        self::LEVEL_INFO => 'INF',
        self::LEVEL_WARN => 'WAR',
        self::LEVEL_ERROR => 'ERR',
        self::LEVEL_SQL => 'SQL',
        self::LEVEL_SYS => 'SYS',
        self::LEVEL_UNKNOW => 'UNKNOW',
    ];

    private static $levelColors = [
        self::LEVEL_TRACE => '32',
        self::LEVEL_INFO => '33',
        self::LEVEL_WARN => '35',
        self::LEVEL_ERROR => '31',
        self::LEVEL_SQL => '34',
        self::LEVEL_SYS => '36',
        self::LEVEL_XWORK_DEV => '32',
        self::LEVEL_UNKNOW => '37',
    ];

    /**
     * @return array
     */
    public static function getLevelStrs(): array {
        return self::$levelStrs;
    }

    /**
     * @return array
     */
    public static function getLevelColors(): array {
        return self::$levelColors;
    }

    public static function getLevelColor($level) {
        return self::$levelColors[$level] ?? self::$levelColors[self::LEVEL_UNKNOW];
    }

    public static function getLevelStr($level) {
        return self::$levelStrs[$level] ?? self::$levelStrs[self::LEVEL_UNKNOW];
    }

    public static function getColorLevelStr($level) {
        $openColor = Config::getConfig('open_color_log', true);
        $levelStr = self::getLevelStr($level);
        if (!$openColor) {
            return "[$levelStr] ";
        }
        $levelColor = self::getLevelColor($level);
        return "\e[" . $levelColor . "m[" . $levelStr . "]\e[m ";
    }

    public static function couldLog($level) {
        $openLog = true;
        if ($level == self::LEVEL_SYS) {
            $openLog = Config::getConfig('open_sys_log', true);
        } else if ($level == self::LEVEL_SQL) {
            $openLog = Config::getConfig('open_sql_log', true);
        }

        $logLevel = Config::getConfig('log_level', self::LEVEL_TRACE);
        return $openLog && $level >= $logLevel;
    }

    public static function couldWrite2ErrorLog($level) {
        return $level != self::LEVEL_SYS && $level != self::LEVEL_SQL && $level != self::LEVEL_XWORK_DEV && $level >= self::LEVEL_WARN;
    }

}
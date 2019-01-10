<?php
namespace Xwork\xcommon\log;
use Xwork\xcommon\INoticer;
use Xwork\xcommon\XUtility;
use Xwork\xmap\Entity;
use Xwork\xmap\EntityBase;

/**
 * Class Debug
 * @package Xwork\xcommon
 * @desc        Debug,记录日志
 * @remark        依赖类:    XUtility , Config , Noticer[其实只依赖Noticer接口概念]
 * @copyright    (c)2012 xwork.
 * @file        Debug.class.php
 * @author        shijianping <shijpcn@qq.com>
 * @date        2012-02-26
 */
class Log
{

    //是否已经flush
    private static $flushed = false;
    // 通知消息
    public static $noticeSmsMsg = "";

    // 当前纳秒值
    private static $ns = 0;

    // 工作单元唯一号,当前纳秒值,修正了后6位
    private static $unitofworkId = 0;

    // 工作单元步骤号
    private static $unitofworkStep = 0;

    // 合并的xworklogStr
    private static $mergeXworklogStr = "";

    // 合并的xworklogErrorStr
    private static $mergeXworklogErrorStr = "";

    // 缓存sql语句
    private static $sqls = array();

    // 统计sql执行时间
    private static $sqltimesum = 0.0;

    // 定时脚本的名称
    private static $cronName = '';

    /**
     * 日志保存器
     * @var array
     */
    private static $loggers;

    // 设置第一个起始时间点
    public static $timeStart = null;

    public static $noticeArr = array();
    /**
     * @var INoticer $noticer
     */
    private static $noticer = null;

    const LOGTYPE_NORMAL = 1;
    const LOGTYPE_WARN = 2;


    // 将自定义调试信息记录到错误日志 trace < info < warn < error
    private static function log($params, $logLevel) {
        if (!LogLevel::couldLog($logLevel)) {
            return false;
        }

        $str = self::combineLogStr($params, $logLevel);
        if ($str === false) {
            return false;
        }

        // 需要主动初始化
        if (self::$unitofworkId < 1) {
            return false;
        }

        $str = self::mergeLine($str);

        $unitofworkIdAndStep = self::getUnitofworkIdAndStep();
        $costTimeFromStartStr = self::getCostTimeFromStartStr();
        $str = XUtility::time_millsecond_strNoDay() . " {$unitofworkIdAndStep} [{$costTimeFromStartStr}] {$str}\n";

        // 合并日志
        self::$mergeXworklogStr .= $str;
        if (LogLevel::couldWrite2ErrorLog($logLevel)) {
            self::$mergeXworklogErrorStr .= $str;
        }
        return true;
    }

    // 将日志多行合并为一行
    private static function mergeLine($str) {
        $str = str_replace("\r", ' ', $str);
        $str = str_replace("\n", ' ', $str);
        $str = str_replace("\t", ' ', $str);
        $str = str_replace('    ', ' ', $str);
        $str = str_replace('    ', ' ', $str);
        $str = str_replace('   ', ' ', $str);
        $str = str_replace('  ', ' ', $str);
        $str = str_replace('  ', ' ', $str);
        return $str;
    }

    // 生成请求的第0条日志, NOTICE
    private static function getNoticeStr() {
        $timeStr = XUtility::time_millsecond_strNoDay(self::$timeStart);

        if (!getenv('HTTP_HOST')) {
            $noticeStr = $timeStr . " [" . self::$unitofworkId . "][ 0] \e[32m[SCRIPT]\e[m";

            $noticeStr .= " \e[33m[" . self::$cronName . "]\e[m";
            $noticeStr .= "\n";
            return $noticeStr;
        }

        $theUrl = "http://" . getenv('HTTP_HOST') . "" . getenv('REQUEST_URI');
        $refererUrl = getenv('HTTP_REFERER');
        $_USER_AGENT = getenv("HTTP_USER_AGENT");
        $client_ip = XUtility::getonlineip();
        $noticeStr = "\e[35m[NTC]\e[m ";
        $request_method = getenv('REQUEST_METHOD');

        $noticeStr .= "[-- ";
        $noticeStr .= "[server_ip = {$_SERVER["SERVER_ADDR"]}] ";
        $noticeStr .= "[client_ip = {$client_ip}] ";
        $noticeStr .= "[request_method = {$request_method}] ";
        $noticeStr .= "[theUrl = {$theUrl}] ";
        $noticeStr .= "[referer = {$refererUrl}] ";
        $noticeStr .= "[_USER_AGENT = {$_USER_AGENT}] ";


        $headerStr = json_encode(getallheaders(), JSON_UNESCAPED_UNICODE);
        $noticeStr .= "[headers {$headerStr}] ";

        $postStr = json_encode($_POST, JSON_UNESCAPED_UNICODE);
        if (false == empty($_POST) && false == empty($postStr)) {
            $noticeStr .= "[posts = {$postStr}] ";
        } else {
            $noticeStr .= '[posts = ] ';
        }

        $inputstr = file_get_contents('php://input');
        if(false == empty($inputstr)){
            $noticeStr .= "[php://input = {$inputstr}] ";
        }

        $cookieStr = json_encode($_COOKIE, JSON_UNESCAPED_UNICODE);

        if (false == empty($_COOKIE) && false == empty($cookieStr)) {
            $noticeStr .= "[cookies = {$cookieStr}] ";
        }

        $noticeStr .= " --] ";

        if (self::$noticeArr) {
            $noticeStr .= implode(' ', self::$noticeArr);
        }

        $noticeStr = self::mergeLine($noticeStr);

        $noticeStr = $timeStr . " [" . self::$unitofworkId . "][ 0] [00.000 ms] {$noticeStr}\n";

        return $noticeStr;
    }

    // 获取时间差, 字符串
    private static function getCostTimeFromStartStr() {
        if (empty(self::$timeStart)) {
            return "";
        } else {
            $timeSpan = XUtility::getCostTime(self::$timeStart);
            $timeSpan = XUtility::trimTimeSpan($timeSpan);
            return "{$timeSpan} ms";
        }
    }

    //全部日志写文件
    private static function saveLog($str, $logtype) {
        foreach (self::getLogger() as $logSaver) {
            if ($logSaver instanceof ILogger) {
                $logSaver->save($str, $logtype);
            } else {
                self::error('logsaver is not instanceof ILogSaver', $logSaver);
            }
        }
    }

    // 初始化
    private static function getUnitofworkIdImp() {
        // 未初始化
        if (self::$unitofworkId < 1) {

            $X_REQUEST_ID = $_SERVER['X_REQUEST_ID'] ?? 0;

            // 10位数字, 秒, 1234567890 => 2009-02-14 07:31:30
            if ($X_REQUEST_ID > 1234567890123456789) {
                // 19位数字, 纳秒级
                $ms = $X_REQUEST_ID / 1000000;
            } elseif ($X_REQUEST_ID > 1234567890123456) {
                // 16位数字, 微妙级
                $ms = $X_REQUEST_ID / 1000;
            } elseif ($X_REQUEST_ID > 1234567890123) {
                // 13位数字, 毫秒
                $ms = $X_REQUEST_ID;
            } else {
                // 获取毫秒 = 秒*1000
                $ms = microtime(true) * 1000;
            }

            // 取整
            $ms = sprintf("%d", $ms);

            // 微妙级 => 纳秒级
            $ns = $ms * 1000000;

            // 缓存
            self::$ns = $ns;

            // 用id生成器取一个id
            $nextId = EntityBase::createID();

            // 修正末6位
            $unitofworkId = self::$ns + $nextId % 1000000;

            self::$unitofworkId = $unitofworkId;
        }

        return self::$unitofworkId;
    }

    // 获取工作单元唯一号+步骤号
    private static function getUnitofworkIdAndStep() {
        self::getUnitofworkIdImp();

        self::$unitofworkStep++;

        return sprintf("[%s][%2d]", self::$unitofworkId, self::$unitofworkStep);
    }

    private static function combineLogStr($params, $logLevel) {
        if (count($params) < 1) {
            return false;
        }
        foreach ($params as &$param) {
            if ($param instanceof Entity) {
                $param = "(Entity:" . $param->getClassName() . ")" . json_encode($param->toArray(), JSON_UNESCAPED_UNICODE);
            } else if ($param instanceof \stdClass || is_array($param)) {
                $param = json_encode($param, JSON_UNESCAPED_UNICODE);
            } else if (is_object($param)) {
                //类实例需要实现JsonSerializable接口才能encode
                $className = get_class($param);
                $tmp = "($className)";
                if ($param instanceof \JsonSerializable) {
                    $tmp .= json_encode($param, JSON_UNESCAPED_UNICODE);
                }
                $param = $tmp;
            } else if (is_bool($param)) {
                $param = $param === true ? '(bool)true' : '(bool)false';
            }
        }
        $str = implode(' ', $params);
        $str = LogLevel::getColorLevelStr($logLevel) . $str;
        return $str;
    }

    public static function mark_timeStart() {
        self::$timeStart = XUtility::getStartTime();
    }

    public static function setCronName($cronName) {
        self::$cronName = $cronName;
    }

    public static function getCronName() {
        return self::$cronName;
    }

    // 记录总耗时, 截至当前时刻,全部耗时信息字符串
    public static function logCostTimeStr($pos = 'where: ', $isSYS = true) {
        $timeSpan = XUtility::getCostTime(self::$timeStart);
        $timeSpan = XUtility::trimTimeSpan($timeSpan);
        $sqltimesum = self::getSqltimesum();
        if ($isSYS) {
            self::sys("[-- {$pos} {$timeSpan} ms | {$sqltimesum} ms --]");
        } else {
            self::trace("[-- {$pos} {$timeSpan} ms | {$sqltimesum} ms --]");
        }
    }

    // 从启动时刻到现在耗时
    public static function getCostTimeFromStart() {
        return XUtility::getCostTime(self::$timeStart);
    }

    // 写硬盘,如果没有主动调用,最好由ob_start 的callback 来调用
    public static function flushXworklog() {
        if (self::$flushed || empty(self::$mergeXworklogStr)) {
            return;
        }

        // 记录总耗时
        self::logCostTimeStr('RequestEnd: ');

        // 补一条 xwork-end
        self::sys("[---------- XWorkEnd ----------]");

        // notice 加入总耗时
        self::addNotice(self::getCostTimeFromStartStr());

        // NoticeStr
        $noticeStr = self::getNoticeStr();
        // str
        $str = "\n\n" . $noticeStr;
        // mergeXworklogStr
        $str .= self::$mergeXworklogStr;

        $filename = date("Ymd", time()) . ".log";
        self::saveLog($str, self::LOGTYPE_NORMAL); // 写日志文件
        self::$mergeXworklogStr = ""; // 重置

        $errorStr = self::$mergeXworklogErrorStr;

        $filename = date("Ymd", time()) . ".error.log";
        self::saveLog($errorStr, self::LOGTYPE_WARN); // 写error日志文件
        self::$mergeXworklogErrorStr = ""; // 重置

        // 通知
        if ($errorStr && self::$noticer instanceof INoticer) {
            self::$noticer->send(self::$unitofworkId, $errorStr, $str);
        }

        self::$unitofworkId = 0;
        self::$unitofworkStep = 0;
        self::$flushed = true;
    }

    // unitofworkId ++
    // 多次提交工作单元
    public static function plusplusUnitofworkId() {
        self::getUnitofworkIdImp();

        // 用id生成器取一个id
        $nextId = EntityBase::createID();

        // 修正末6位
        self::$unitofworkId = self::$ns + $nextId % 1000000;
    }

    // 初始化 UnitofworkId
    public static function initUnitofworkId() {
        self::getUnitofworkIdImp();
    }

    // 监控用途
    public static function getUnitofworkId() {
        return self::getUnitofworkIdImp();
    }

    // 记录sql语句
    public static function addSql($sql, $timespan, $isquery = true) {
        self::$sqltimesum += $timespan;

        $sqlItem = array();
        $sqlItem["sql"] = $sql;
        $sqlItem["timespan"] = $timespan;
        // $sqlItem["isquery"] = $isquery;
        self::$sqls[] = $sqlItem;
    }

    // 获得sql执行时间，并取整
    public static function getSqltimesum() {
        return XUtility::trimTimeSpan(self::$sqltimesum);
    }

    public static function addLogger(ILogger $logger) {
        self::$loggers[] = $logger;
    }

    public static function getLogger() {
        return self::$loggers;
    }

    // 添加 notice 头
    public static function addNotice($str) {
        if (strpos($str, "[--") === false) {
            $str = "[-- {$str} --]";
        }
        self::$noticeArr[] = $str;
    }

    // 追踪, 程序的运行过程, 工程师主用的一个级别
    // 可以通过配置文件关闭线上trace日志
    public static function trace(...$params) {
        self::log($params, LogLevel::LEVEL_TRACE);
    }

    // sql, 执行日志, 可以理解为特殊的trace日志
    public static function sql(...$params) {
        self::log($params, LogLevel::LEVEL_SQL);
    }

    // 系统, 框架层运行日志, 业务层不要用
    public static function sys(...$params) {
        self::log($params, LogLevel::LEVEL_SYS);
    }

    // 重要信息, 用于记录不发消息的 warn (sjp: 本级别日志的含义是修改过了的)
    // 用[INF]是为了和[SQL]对齐
    public static function info(...$params) {
        self::log($params, LogLevel::LEVEL_INFO);
    }

    // 会出现潜在错误的情形; (sjp: 可以理解为重点跟踪的info)
    // 同时记录到 .error.txt
    public static function warn(...$params) {
        self::log($params, LogLevel::LEVEL_WARN);
    }

    // 指出虽然发生错误事件，但仍然不影响系统的继续运行; (log4j::error的概念)
    // 系统致命错误; (log4j::fatal的概念)
    // 同时记录到 .error.txt
    public static function error(...$params) {
        self::log($params, LogLevel::LEVEL_ERROR);
    }
}

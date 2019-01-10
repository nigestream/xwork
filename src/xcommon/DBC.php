<?php
namespace Xwork\xcommon;
use Xwork\xexception\AssertException;

/**
 * DBC
 * @desc		异常类,断言类
 * @remark		依赖类:	Debug
 * @copyright	(c)2012 xwork.
 * @file		DBC.class.php
 * @author		shijianping <shijpcn@qq.com>
 * @date		2012-02-26
 */

// 断言类
class DBC
{
    // 是否检查断言
    public static $needDBC = 0;
    // 断言为真
    public static function requireTrue ($express, $message = "", $errorCode = -1, $logMessage = '') {
        if (self::$needDBC <= 0 && false == $express) {
            throw new AssertException($message, $errorCode, $logMessage);
        }
    }

    // 断言不为真
    public static function requireNotTrue ($express, $message = "", $errorCode = -1, $logMessage = '') {
        self::requireTrue(false == $express, $message, $errorCode, $logMessage);
    }
    // 断言非null
    public static function requireNotNull ($obj, $message = "", $errorCode = -1, $logMessage = '') {
        self::requireTrue($obj !== null, $message, $errorCode, $logMessage);
    }
    // 断言为null
    public static function requireNull ($obj, $message = "", $errorCode = -1, $logMessage = '') {
        self::requireTrue($obj === null, $message, $errorCode, $logMessage);
    }
    // 断言不是空字符串
    public static function requireNotEmptyString ($str, $message = "", $errorCode = -1, $logMessage = '') {
        self::requireTrue($str != null, $message, $errorCode, $logMessage);
        self::requireTrue(trim($str) != '', $message, $errorCode, $logMessage);
    }
    // 断言非空
    public static function requireNotEmpty ($obj, $message = "", $errorCode = -1, $logMessage = '') {
        $_obj = $obj;
        self::requireTrue(! empty($_obj), $message, $errorCode, $logMessage);
    }
    // 断言为空
    public static function requireEmpty ($obj, $message = "", $errorCode = -1, $logMessage = '') {
        $_obj = $obj;
        self::requireTrue(empty($_obj), $message, $errorCode, $logMessage);
    }
    // 断言相等
    public static function requireEquals ($first, $second, $message = "", $errorCode = 0) {
        self::requireTrue($first == $second, $message, $errorCode);
    }
    // 断言不等
    public static function requireNotEquals ($first, $second, $message = "", $errorCode = 0) {
        self::requireTrue($first != $second, $message, $errorCode);
    }
    // 断言小于
    public static function requireLess ($first, $second, $message = "", $errorCode = 0) {
        self::requireTrue($first < $second, $message, $errorCode);
    }
    // 断言大于
    public static function requireMore ($first, $second, $message = "", $errorCode = 0) {
        self::requireTrue($first > $second, $message, $errorCode);
    }
    // 断言大于等于
    public static function requireMoreThan ($first, $second, $message = "", $errorCode = 0) {
        self::requireTrue($first >= $second, $message, $errorCode);
    }
    // 断言小于等于
    public static function requireLessThan ($first, $second, $message = "", $errorCode = 0) {
        self::requireTrue($first <= $second, $message, $errorCode);
    }
    // 断言处于之间
    public static function requireBetween ($obj, $min, $max, $message = "", $errorCode = 0) {
        if (is_string($obj)) {
            $len = strlen($obj);
            self::requireTrue($len >= $min && $len <= $max, $message, $errorCode);
        } else {
            self::requireTrue($obj >= $min && $obj <= $max, $message, $errorCode);
        }
    }
}


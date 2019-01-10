<?php
namespace Xwork\xcommon;
use Xwork\xmvc\XRequest;

/**
 * XCookie
 * @desc 		安全 cookie
 * @remark		依赖类: 无
 * @copyright 	(c)2012 xwork.
 * @file		XCookie.class.php
 * @author		shijianping <shijpcn@qq.com>
 * @date		2012-02-26
 */
class XCookie
{

    public static function set0 ($key, $value, $expire = 0, $domain = '', $path = "/") {
        if (empty($domain)) {
            $domain = Config::getConfig("website_domain");
            // $domain = XUtility::getDomainByHost(getenv('HTTP_HOST'));
        }

        setcookie($key, $value, $expire, $path, $domain);
    }

    public static function set ($key, $value, $expire = 0, $domain = '', $path = "/") {
        if (empty($domain)) {
            $domain = Config::getConfig("website_domain");
            // $domain = XUtility::getDomainByHost(getenv('HTTP_HOST'));
        }

        $t = time();
        $m = self::mcode($t, $value);
        $j = "{$t}|x|{$value}|x|{$m}";

        setcookie($key, $j, $expire, $path, $domain);
        return $j;
    }

    public static function get0($key) {
        return isset($_COOKIE[$key]) ? $_COOKIE[$key] : '';
    }

    public static function get ($key) {
        $cook = XRequest::getValue($key, '');
        if (empty($cook)) {
            $cook = @$_COOKIE[$key];
            if (empty($cook)) {
                //auth-token
                $cook = urldecode($_SERVER['HTTP_AUTH_TOKEN']);
                if (empty($cook)) {
                    return "";
                }
            }
        }
        list ($t, $value, $m) = explode("|x|", $cook);
        $m0 = self::mcode($t, $value);
        if ($m0 == $m) {
            return $value;
        } else {
            return "";
        }
    }

    public static function mcode ($t, $value) {
        $xcookie_prekey = Config::getConfig("xcookie_prekey", "xcookie_prekey");
        return md5("{$xcookie_prekey},{$t},{$value}");
    }
}

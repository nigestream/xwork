<?php
namespace Xwork\xmvc;
/**
 * XRequest
 * @desc 		封装 $_REQUEST 以及 urlRewrite的处理
 * @remark		依赖类: 无
 * @copyright	(c)2012 xwork.
 * @file		XRequest.class.php
 * @author		shijianping <shijpcn@qq.com>
 * @date		2012-02-26
 */
class XRequest
{

    private static $rewrites = array();

    public static function setRewrites ($rewrites) {
        if (! empty($rewrites))
            self::$rewrites = $rewrites;
    }

    // 按照特定规则进行url rewrite处理:进行参数提取
    public static function rewriteRequestByUrl () {
        $path = self::getUriPath();
        $path = self::rewritePath($path);
    }

    private static function getUriPath () {
        $uri = parse_url(getenv('REQUEST_URI'));
        return $uri["path"];
    }

    private static function rewritePath ($path) {
        foreach (self::$rewrites as $pattern => $replacement) {
            $path0 = preg_replace($pattern, $replacement, $path);

            // TODO by sjp 2011-09-15: 需要用 preg_match 修正一下本逻辑

            // echo "<br/>";
            // print_r($pattern);
            // echo " : ";
            // print_r($path0);

            // 命中
            if ($path0 != $path) {
                $path = $path0;

                $arr = parse_url($path0);
                $query = $arr['query'];
                $params = explode("&", $query);
                foreach ($params as $param) {
                    $key_values = explode("=", $param);

                    // TODO modify by sjp 2011-09-26 : 问号后边 或 post 或 cookie
                    // 的参数优先于 url rewrite ；需要测试
                    if (! isset($_REQUEST[$key_values[0]])) {
                        $_REQUEST[$key_values[0]] = $key_values[1];
                    }
                }

                break;
            }
        }
        return $path;
    }

    // 不安全模式 (未安全处理过的值)
    public static function getUnSafeValue ($key, $default = null) {
        return self::getValueEx($key, $default, false);
    }

    // 安全模式
    public static function getValue ($key, $default = null) {
        return self::getValueEx($key, $default, true);
    }

    // 私有方法
    private static function getValueEx ($key, $default = null, $needSafe = true) {
        $value = self::getValueImp($key, $needSafe);
        return ($value == null && $default !== null) ? $default : $value;
    }

    // 私有方法
    private static function getValueImp ($key, $needSafe = true) {
        $request = self::getRequest();

        if (isset($request[$key])) {

            // 过滤恶意代码
            if ($needSafe) {
                $request[$key] = self::filter($request[$key]);
            }

            if (is_string($request[$key])) {
                $request[$key] = trim($request[$key]);
            }

            return $request[$key];
        }

        // 向前兼容
        if ($key == 'xaction') {
            return self::getValueImp("action");
        }

        return null;
    }

    // 安全模式, 安全处理过的cookie
    public static function getCookie ($key, $default = null) {
        return self::getCookieEx($key, $default, true);
    }

    // 私有方法
    private static function getCookieEx ($key, $default = null, $needSafe = true) {
        $value = self::getCookieImp($key, $needSafe);
        return $value !== null ? $value : $default;
    }

    // 私有方法
    private static function getCookieImp ($key, $needSafe = true) {
        if (isset($_COOKIE[$key])) {
            $cookie = $_COOKIE[$key];

            // 过滤恶意代码
            if ($needSafe) {
                $cookie = self::filter($cookie);
            }

            if (is_string($cookie)) {
                $cookie = trim($cookie);
            }

            return $cookie;
        }

        return null;
    }

    // request = (get+post+cookie) get > post > cookie
    public static function getRequest () {
        $_REQUEST += $_COOKIE;
        return $_REQUEST;
    }

    // 没有调用点
    public static function setValue ($key, $value) {
        $_REQUEST["$key"] = trim($value);
        return true;
    }

    // 覆盖和追加
    public static function setRequest ($requestArray) {
        foreach ($requestArray as $key => $value) {
            $_REQUEST["$key"] = $value;
        }
        return true;
    }

    public static function getHttpReferer () {
        return getenv('HTTP_REFERER');
    }

    public static function getHeader ($key) {
        $header = getallheaders();
        return $header[$key];
    }

    // 递归
    private static function filter ($sets) {
        if (is_array($sets)) {
            foreach ($sets as $key => $set) {
                if (is_array($set)) {
                    $set = self::filter($set);
                } else {
                    // $set = filter_var($set, FILTER_SANITIZE_SPECIAL_CHARS);
                    $set = self::replace($set);
                    $set = trim(filter_var($set, FILTER_SANITIZE_STRIPPED));
                }
                $sets[$key] = $set;
            }
        } else
            if (null !== $sets) {
                // $sets = filter_var($sets, FILTER_SANITIZE_SPECIAL_CHARS);
                $sets = self::replace($sets);
                $sets = trim(filter_var($sets, FILTER_SANITIZE_STRIPPED));
            }
        return $sets;
    }

    // 替换 < 和 > 标签
    private static function replace ($value) {
        $replaceMap = array(
            '<' => '&#60;',
            '>' => '&#62;');
        foreach ($replaceMap as $key => $code) {
            $value = str_replace($key, $code, $value);
        }
        return $value;
    }
}

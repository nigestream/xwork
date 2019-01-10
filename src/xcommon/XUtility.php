<?php
namespace Xwork\xcommon;
use Xwork\xcommon\log\Log;

/**
 * XUtility
 * @desc 		万能工具类,全是静态方法;[TODO by sjp :有时间时需要瘦一下身]
 * @remark		依赖类:DBC, 个别方法可能依赖个别类
 * @copyright 	(c)2012 xwork.
 * @file		XUtility.class.php
 * @author		shijianping <shijpcn@qq.com>
 * @date		2012-02-26
 */
class XUtility
{

    public static function arrayStr2Lower ($row) {
        if (empty($row)) {
            return array();
        }
        $newRow = array();
        foreach ($row as $k => $v) {
            $newRow[$k] = strtolower($v);
        }

        return $newRow;
    }


    public static function jsonArrayFix($arr) {
        $arr1 = array();
        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                $arr1[$k] = self::jsonArrayFix($v);
            } else {
                $arr1[$k] = "{$v}";
            }
        }

        return $arr1;
    }

    /*
     * 读取一个文件,当header头输出 选定目录，然后在选定的目录下，创建以$filename命名的文件
     */
    public static function outputheader ($filename) {
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:");
        header("Cache-Control: public");
        header("Content-Description: File Transfer");
        // Use the switch-generated Content-Type
        header("Content-Type: application/force-download");
        $header = 'Content-Disposition: attachment; filename=' . $filename . ';';
        header($header);
        header("Content-Transfer-Encoding: binary");
    }

    /*
     * 计算税率,可能过时了
     */
    public static function doTax ($money) {
        if ($money <= 800) {
            return 0;
        } elseif ($money > 800 && $money <= 4000) {
            $real = ($money - 800) * 0.2;
        } elseif ($money > 4000 && $money <= 20000) {
            $real = $money * 0.8 * 0.2;
        } elseif ($money > 20000 && $money <= 50000) {
            $real = $money * 0.8 * 0.3 - 2000;
        } elseif ($money > 50000) {
            $real = $money * 0.8 * 0.4 - 7000;
        }

        return $real;
    }

    /*
     * 全角数字转半角数字 SBC case to DBC case
     */
    public static function SBC2DBCNubmer ($str) {
        $values = array(
            '０' => '0',
            '１' => '1',
            '２' => '2',
            '３' => '3',
            '４' => '4',
            '５' => '5',
            '６' => '6',
            '７' => '7',
            '８' => '8',
            '９' => '9',
            '。' => '.',
            '．' => '.');
        foreach ($values as $key => $value) {
            $str = str_replace($key, $value, $str);
        }
        return $str;
    }

    /*
     * 全角=>半角
     */
    public static function SBC_DBC ($str) {
        $queue = Array(
            '０' => '0',
            '１' => '1',
            '２' => '2',
            '３' => '3',
            '４' => '4',
            '５' => '5',
            '６' => '6',
            '７' => '7',
            '８' => '8',
            '９' => '9',
            'Ａ' => 'A',
            'Ｂ' => 'B',
            'Ｃ' => 'C',
            'Ｄ' => 'D',
            'Ｅ' => 'E',
            'Ｆ' => 'F',
            'Ｇ' => 'G',
            'Ｈ' => 'H',
            'Ｉ' => 'I',
            'Ｊ' => 'J',
            'Ｋ' => 'K',
            'Ｌ' => 'L',
            'Ｍ' => 'M',
            'Ｎ' => 'N',
            'Ｏ' => 'O',
            'Ｐ' => 'P',
            'Ｑ' => 'Q',
            'Ｒ' => 'R',
            'Ｓ' => 'S',
            'Ｔ' => 'T',
            'Ｕ' => 'U',
            'Ｖ' => 'V',
            'Ｗ' => 'W',
            'Ｘ' => 'X',
            'Ｙ' => 'Y',
            'Ｚ' => 'Z',
            'ａ' => 'a',
            'ｂ' => 'b',
            'ｃ' => 'c',
            'ｄ' => 'd',
            'ｅ' => 'e',
            'ｆ' => 'f',
            'ｇ' => 'g',
            'ｈ' => 'h',
            'ｉ' => 'i',
            'ｊ' => 'j',
            'ｋ' => 'k',
            'ｌ' => 'l',
            'ｍ' => 'm',
            'ｎ' => 'n',
            'ｏ' => 'o',
            'ｐ' => 'p',
            'ｑ' => 'q',
            'ｒ' => 'r',
            'ｓ' => 's',
            'ｔ' => 't',
            'ｕ' => 'u',
            'ｖ' => 'v',
            'ｗ' => 'w',
            'ｘ' => 'x',
            'ｙ' => 'y',
            'ｚ' => 'z',
            '　' => ' ',
            '。' => '.',
            '，' => ',',
            '～' => '~',
            '！' => '!',
            '＠' => '@',
            '＃' => '#',
            '＄' => '$',
            '％' => '%',
            '︿' => '^',
            '＆' => '&',
            '×' => '*',
            '（' => '(',
            '）' => ')',
            '＿' => '_',
            '＋' => '+',
            '｜' => '|',
            '－' => '-',
            '＝' => '=',
            '｛' => ' ',
            '｝' => '}',
            '［' => '[',
            '］' => ']',
            '：' => ':',
            '“' => '"',
            '；' => ';',
            '‘' => '\'',
            '《' => '<',
            '》' => '>',
            '？' => '?',
            '／' => '/');
        return str_replace(array_keys($queue), array_values($queue), $str);
    }

    /*
     * 获取一个类的属性: invoke fields get function TODO by sjp:需要再测试一下
     */
    public static function object2Array ($object) {
        $fields = $object->getFields();
        $results = array();
        foreach ($fields as $field) {
            $methodStr = "get" . $field;
            $results[$field] = $object->$methodStr();
        }
        return $results;
    }

    /*
     * guid
     */
    public static function guid () {
        return md5(uniqid(rand(), true));
    }

    /*
     * 页面抓取
     */
    public static function fetchPage ($url_file) {
        $url_file = trim($url_file);
        if (empty($url_file))
            return false;
        $url_arr = parse_url($url_file);
        if (! is_array($url_arr) || empty($url_arr))
            return false;
        // 获取请求数据
        $host = $url_arr['host'];
        $path = $url_arr['path'] . "?" . $url_arr['query'];
        $port = isset($url_arr['port']) ? $url_arr['port'] : "80";
        // 连接服务器
        $fp = fsockopen($host, $port, $err_no, $err_str, 120);
        if (! $fp) {
            echo "{$err_str} ({$err_no})<br />\n";
            return false;
        }
        // 构造请求协议
        $request_str = "GET " . $path . " HTTP/1.1\r\n";
        $request_str .= "Host: " . $host . "\r\n";
        $request_str .= "Connection: Close\r\n\r\n";

        // 发送请求
        fwrite($fp, $request_str);
        stream_set_timeout($fp, 60);
        $content = "";
        while (! feof($fp)) {
            $content .= fgets($fp, 128);
        }
        fclose($fp);
        return $content;
    }

    /*
     * 在url中获取domain
     */
    public static function getDomainByUrl ($url) {
        $array = parse_url($url);
        $host = $array["host"];
        return self::getDomainByHost($host);
    }

    /*
     * 在url中获取host
     */
    public static function getHostByUrl ($url) {
        $array = parse_url($url);
        return $array["host"];
    }

    /*
     * 通过url获取 uri
     */
    public static function getUri ($url) {
        $arr = parse_url($url);
        return $arr['scheme'] . '://' . $arr['host'];
    }

    /*
     * 通过$host获取domain
     */
    public static function getDomainByHost ($host) {
        $strs = explode('.', $host);

        // yyy.com
        if (count($strs) < 3) {
            return $host;
        }

        $strs = array_reverse($strs);

        $topSuffix = array(
            'com',
            'net',
            'org',
            'gov',
            'edu',
            'info',
            'name',
            'tv',
            'sh',
            'travel',
            'ac',
            'cc',
            'io',
            'ws',
            'biz',
            'mobi');

        if (in_array($strs[0], $topSuffix)) {
            // xxx.yyy.com
            return $strs[1] . "." . $strs[0];
        } elseif (in_array($strs[1], $topSuffix)) {
            // xxx.yyy.com.cn
            return $strs[2] . "." . $strs[1] . "." . $strs[0];
        } elseif (count($strs) == 3) {
            // xxx.yyy.cn
            return $strs[1] . "." . $strs[0];
        } else {
            // xxx.yyy.zzz.bj.cn
            return $strs[2] . "." . $strs[1] . "." . $strs[0];
        }
    }

    /*
     * 获取客户端IP
     */
    public static function getonlineip () {
        $onlineip = "";

        if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown")) {
            $onlineip = getenv("HTTP_CLIENT_IP");
        } elseif (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDER_FOR"), "unknown")) {
            $onlineip = getenv("HTTP_X_FORWARDED_FOR");
        } elseif (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown")) {
            $onlineip = getenv("REMOTE_ADDR");
        } elseif (isset($_SERVER["REMOTE_ADDR"]) && $_SERVER["REMOTE_ADDR"] && strcasecmp($_SERVER["REMOTE_ADDR"], "unknown")) {
            $onlineip = $_SERVER["REMOTE_ADDR"];
        }
        $onlineip = preg_replace("/^([\d\.]+).*/", "\\1", $onlineip);

        return $onlineip;
    }

    /*
     * 获取ip整数值 依赖 ../xutil/Ip.class.php
     */
    public static function getIntIp () {
        $ipstr = self::getonlineip();
        return Ip::ipToInt($ipstr);
    }

    /*
     * 获取cookie值
     */
    public static function getcookie ($cookiename, $key = "") {
        $cookievalue = $_COOKIE[$cookiename];
        if ($cookievalue == "" || ! isset($cookievalue) || $cookievalue == null) {
            return false;
        }

        if ($key == "") {
            return $cookievalue;
        }

        $array_cookie = self::splitcookie($cookievalue);

        if ($key == "") {
            return $array_cookie;
        }

        return $array_cookie[$key];
    }

    /*
     * 按照规则解析cookie字符串，获取cookie数组
     */
    public static function splitcookie ($cookievalue) {
        $array_cookie = array();
        /*
         * 格式：key1:val1/key2:val2/key3:val3
         */
        $list = explode("/", $cookievalue);
        foreach ($list as $str) {
            $tmp = explode(':', $str);
            $key = $tmp[0];
            $val = $tmp[1];
            $array_cookie[$key] = $val;
        }

        return $array_cookie;
    }

    /*
     * 写cookie
     */
    public static function writecookie ($cookieinfo, $domain, $path = "/") {
        $cookiename = $cookieinfo["name"];
        $cookievalue = $cookieinfo["value"];
        $cookieexpire = $cookieinfo["expire"];

        if (setcookie($cookiename, $cookievalue, $cookieexpire, $path, $domain)) {
            return true;
        }

        return false;
    }

    /*
     * 清除cookie
     */
    public static function clearcookie ($cookiename, $domain, $path = "/") {
        $expire = time() - 1 * 60 * 60;
        if (setcookie($cookiename, "", $expire, $path, $domain)) {
            return true;
        }

        return false;
    }

    /*
     * 得到当前时间的毫秒数
     */
    public static function microtime_float () {
        list ($usec, $sec) = explode(" ", microtime());
        return ((float) $usec + (float) $sec);
    }

    // 截取毫秒以后的， 如： 916.462898254
    public static function usec () {
        $t = microtime(true);
        return ($t - (int) $t) * 1000;
    }

    // 2007-08-09 08:21:24.916.486978531
    // 由于时间开销所以不重用上面函数
    public static function time_microtime_str () {
        $t = microtime(true);
        $t = ($t - (int) $t) * 1000;
        if ($t > 0 && $t < 10)
            $t = "00" . $t;
        elseif ($t > 10 && $t < 100)
            $t = "0" . $t;
        return date("Y-m-d H:i:s", time()) . "." . substr($t, 0, 7);
    }

    // 精确到微妙
    public static function time_microtime_strNoDay ($t = 0) {
        if (empty($t)) {
            $t = microtime(true);
        }
        $t = ($t - (int) $t) * 1000;
        if ($t > 0 && $t < 10) {
            $t = "00" . $t;
        } elseif ($t > 10 && $t < 100) {
            $t = "0" . $t;
        }
        return date("H:i:s", time()) . "." . substr($t, 0, 7);
    }

    // 精确到毫秒
    public static function time_millsecond_strNoDay ($t = 0) {
        if (empty($t)) {
            $t = microtime(true);
        }
        $t = ($t - (int) $t) * 1000;
        if ($t > 0 && $t < 10) {
            $t = "00" . $t;
        } elseif ($t > 10 && $t < 100) {
            $t = "0" . $t;
        }
        return date("H:i:s", time()) . "." . substr($t, 0, 3);
    }

    /*
     * 检索目录，返回该目录下文件名或文件夹的名称数组
     */
    public static function scan_dir ($dir) {
        $handle = opendir($dir);

        if (! $handle) {
            echo "opening directory failed!";
            return false;
        }

        while (false !== ($file = readdir($handle))) {
            if ($file != "." && $file != "..") {
                $f[] = $file;
            }
        }

        closedir($handle);

        return $f;
    }

    public static function getStartTime () {
        return microtime(true);
    }

    public static function getCostTime ($timeStart) {
        return (microtime(true) - $timeStart) * 1000;
    }

    public static function trimTimeSpan ($timeSpan, $fixLen = 3, $allLen = 6) {
        $format = "%0{$allLen}.{$fixLen}f";
        return sprintf($format, $timeSpan);
    }

    public static function timeDiffString (XDateTime $d1, XDateTime $d2) {
        if (($ydiff = XDateTime::yearDiff($d1, $d2)) > 1)
            return ($ydiff - 1) . '年前';
        if (($mdiff = XDateTime::monthDiff($d1, $d2)) > 1)
            return ($mdiff - 1) . '个月前';
        if (($ddiff = floor(XDateTime::dayDiff($d1, $d2))) > 0)
            return floor($ddiff) . '天前';
        if (($hdiff = floor(XDateTime::hourDiff($d1, $d2))) > 0)
            return floor($hdiff) . '小时前';
        if (($midiff = floor(XDateTime::minuteDiff($d1, $d2))) > 0)
            return floor($midiff) . '分钟前';

        return "刚刚";
        // return floor(XDateTime::secondDiff($d1,$d2)).'秒前';
    }

    public static function timeDiffString2msg (XDateTime $d1, XDateTime $d2) {
        if (($ydiff = XDateTime::yearDiff($d1, $d2)) > 0)
            return date("Y-m-d H:i", $d2->getTime()) . ' (' . $ydiff . '年前)';
        if (($mdiff = XDateTime::monthDiff($d1, $d2)) > 0)
            return date("Y-m-d H:i", $d2->getTime()) . ' (' . $mdiff . '月前)';
        if (($ddiff = floor(XDateTime::dayDiff($d1, $d2))) > 0)
            return date("Y-m-d H:i", $d2->getTime()) . ' (' . floor($ddiff) . '天前)';
        if (($hdiff = floor(XDateTime::hourDiff($d1, $d2))) > 0)
            return date("Y-m-d H:i", $d2->getTime()) . ' (' . floor($hdiff) . '小时前)';
        if (($midiff = floor(XDateTime::minuteDiff($d1, $d2))) > 0)
            return floor($midiff) . '分钟前';

        return "刚刚";
        // return floor(XDateTime::secondDiff($d1,$d2)).'秒前';
    }

    public static function blurIp ($ip) {
        $pt = "([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)[0-9]{1,3}";
        return preg_replace($pt, "\\1*", $ip);
    }

    /*
     * 二分法标红
     */
    public static function markRed ($str, $markstr, $className = "mark_keyword") {
        $pattern = "|[a-zA-Z]|is";

        if (preg_match($pattern, $markstr)) {
            return $str;
        }

        $markArray = array();
        $markArrayReplace = array();

        $len = mb_strlen($markstr);

        for ($i = 0; $i < $len; $i ++) {
            $char = mb_substr($markstr, $i, 1, "UTF-8");

            if (! trim($char))
                continue;
            if (in_array($char, $markArray))
                continue;
            $markArray[] = $char;
            $markArrayReplace[] = "<span class='{$className}'>{$char}</span>";
        }

        // var_dump($markArray);

        $str = str_replace($markArray, $markArrayReplace, $str);
        $str = str_replace("</span><span class='{$className}'>", '', $str);

        return $str;
    }

    // 返回一个十六进制字符串的 int key,$pow代表2 多少次幂,即 $pow=8 为 0-256范围
    // 此字符串一般为 md5串或串的一部分
    public static function truncationIntKey ($hexstr, $pow = 8) {
        $hexlen = ceil($pow / 4); // 需要截取的字符串长度
        $hexstr0 = substr($hexstr, 0, $hexlen);
        $dec = hexdec($hexstr0);
        return $dec % pow(2, $pow);
    }

    // 截取函数
    public static function cutHtml ($string, $number) {
        // 带空格
        // preg_match_all('/\>([^>]+?)(?=\<)/',$string, $match);
        // 不带空格
        preg_match_all('/(?:\>|&nbsp;)([^>]+?)(?=(?:\<|&nbsp;))/', $string, $match);
        $cutHtml = implode($match[1]);
        return self::cutString($cutHtml, $number);
    }

    // 截取函数
    public static function cutString ($string, $length) {
        preg_match_all(
                "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/",
                $string, $info);
        $str = '';
        $j = 0;
        for ($i = 0; $i < count($info[0]); $i ++) {
            $str .= $info[0][$i];
            $j = ord($info[0][$i]) > 127 ? $j + 2 : $j + 1;
            if ($j > $length - 3) {
                return $str;
            }
        }
        return join('', $info[0]);
    }


    // 自定义empty,解决魔法方法不让empty的缺陷
    public static function MyEmpty ($value) {
        $var = $value;
        return ! (isset($var) && ! empty($var) && ! is_null($var));
    }

    // 将一个时间字符串转换成一个分钟数 04:11 -> 251 (一天中的第xx分钟)
    public static function time2minute ($str) {
        list ($hour, $minute) = explode(':', trim($str));

        if ($minute === null) {
            return - 1;
        }

        $hour = (int) $hour;
        $minute = (int) $minute;
        return $hour * 60 + $minute;
    }

    /*
     * 检查一个图片文件的类型
     */
    public static function checkImgType ($imgurl) {
        $header = file_get_contents($imgurl, 0, NULL, 0, 5);

        // echo bin2hex($header);
        if ($header{0} . $header{1} == "\x89\x50") {
            return 'png';
        } elseif ($header{0} . $header{1} == "\xff\xd8") {
            return 'jpeg';
        } elseif ($header{0} . $header{1} . $header{2} == "\x47\x49\x46") {
            return "gif";
            // if( $header { 4 } == "\x37" )
            // return 'gif87' ;
            // else if( $header { 4 } == "\x39" )
            // return 'gif89' ;
        }
        return false;
    }

    // 十进制转到其他制
    public static function dec2any ($num, $base = 36, $index = false) {
        if (! $base) {
            $base = strlen($index);
        } elseif (! $index) {
            $index = substr("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ", 0, $base);
        }
        $out = "";
        for ($t = floor(log10($num) / log10($base)); $t >= 0; $t --) {
            $a = floor($num / pow($base, $t));
            $out = $out . substr($index, $a, 1);
            $num = $num - ($a * pow($base, $t));
        }
        return $out;
    }

    // 其他进制转10进制
    public static function any2dec ($num, $base = 36, $index = false) {
        if (! $base) {
            $base = strlen($index);
        } elseif (! $index) {
            $index = substr("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ", 0, $base);
        }
        $out = 0;
        $len = strlen($num) - 1;
        for ($t = 0; $t <= $len; $t ++) {
            $out = $out + strpos($index, substr($num, $t, 1)) * pow($base, $len - $t);
        }
        return $out;
    }

    // 对汉字有处理
    public static function my_json_encode ($arr) {
        $arr = self::array_urlencode($arr);
        $str = json_encode($arr);
        $str = urldecode($str);
        return $str;
    }

    // 为 my_json_encode 服务
    private static function array_urlencode ($arr) {
        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                $arr[$k] = self::array_urlencode($v);
            } else {
                $arr[$k] = urlencode($v);
            }
        }

        return $arr;
    }
}

// ///////////////////////////////////////////////////

/**
 * Ip,ip处理类
 *
 * @copyright (c) 2012 xwork.
 *            @file Ip.class.php
 * @author shijianping <shijpcn@qq.com>
 *         @date 2012-02-26
 *         @remark 无依赖类
 */
class Ip
{

    public static function get () {
        if ($_SERVER['HTTP_CLIENT_IP'] && $_SERVER['HTTP_CLIENT_IP'] != 'unknown') {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif ($_SERVER['HTTP_X_FORWARDED_FOR'] && $_SERVER['HTTP_X_FORWARDED_FOR'] != 'unknown') {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    public static function ipToInt ($ip) {
        $ips = explode('.', $ip);
        if (count($ips) >= 4) {
            $int = $ips[0] * 256 * 256 * 256 + $ips[1] * 256 * 256 + $ips[2] * 256 + $ips[3];
        } else {
            Log::error("[-- ip is error:{$ip} --]");
            return - 1;
        }
        return $int;
    }

    public static function isIn ($startIp, $endIp, $ip) {
        $start = Ip::ipToInt($startIp);
        $end = Ip::ipToInt($endIp);
        $ipInt = Ip::ipToInt($ip);
        $result = false;
        if ($ipInt >= $start && $ipInt <= $end) {
            $result = true;
        }
        return $result;
    }
}

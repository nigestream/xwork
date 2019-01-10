<?php

/**
 * XDateTime
 * @desc		时间封装类
 * @remark		依赖类:SystemException
 * @copyright 	(c)2012 xwork.
 * @file		XDateTime.class.php
 * @author		whd
 * @date		2012-02-26
 */
namespace Xwork\xcommon;
use Xwork\xexception\SystemException;

class XDateTimeException extends SystemException
{

    const FORMAT_XDATETIME_ERROR = "转换时间类型失败";
}

class XDateTime
{

    const DEFAULT_DATE_FORMAT = "Y-m-d";

    const DEFAULT_XDATETIME_FORMAT = "Y-m-d H:i:s";

    const DEFAULT_START_TIME = "1970-01-01 00:00:00"; // Unix时间戳 起始时间
    const YEAR = "Y";

    const MONTH = "m";

    const DAY = "d";

    const HOUR = "H";

    const MINUTE = "i";

    const SECOND = "s";

    private static $nowForTest = null;

    private $date; // 日期字符串 例:"2008-09-01 08:00:00"
    protected function __construct ($date) {
        $this->date = $date;
    }
    // 返回 日期字符串 ******start
    public static function today ($format = self::DEFAULT_DATE_FORMAT) {
        return date($format);
    }

    // 返回 昨天日期字符串
    public static function yesterday () {
        return date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - 1, date("Y")));
    }

    // 返回 月第一天字符串 (cnt 0表示本月，1 表示上月 依次轮推)
    public static function firstDayOfMonth ($cnt = 0) {
        return date("Y-m-d", mktime(0, 0, 0, date("m") - $cnt, 1, date("Y")));
    }

    // 返回月最后一天字符串 (cnt 0表示本月，1 表示上月 依次轮推)
    public static function endDayOfMonth ($cnt = 0) {
        return date("Y-m-d", mktime(0, 0, 0, date("m") - $cnt, 31, date("Y")));
    }

    public static function now ($format = self::DEFAULT_XDATETIME_FORMAT) {
        if (self::$nowForTest != null)
            return self::$nowForTest;
        return date($format);
    }
    // *********************end

    public static function getNow () {
        return new XDateTime(XDateTime::now());
    }

    public static function createXDateTime ($year, $month, $day, $hour = "00", $minute = "00", $second = "00") {
        $dateStr = "{$year}-{$month}-{$day} {$hour}:{$minute}:{$second}";
        if (strtotime($dateStr) == false)
            throw new XDateTimeException(XDateTimeException::FORMAT_XDATETIME_ERROR);
        $date = date(self::DEFAULT_XDATETIME_FORMAT, strtotime($dateStr));
        return new XDateTime($date);
    }

    public static function valueOf ($date) {
        if (strtotime($date) == false)
            throw new XDateTimeException(XDateTimeException::FORMAT_XDATETIME_ERROR);
        return new XDateTime($date);
    }

    public static function setNowForTest (XDateTime $now) {
        self::$nowForTest = $now->toString();
    }

    public static function getNowForTest () {
        return self::$nowForTest;
    }

    /*
     * deprecation start 这个是原来程序中的函数, 如果想实现类似功能 调用addDay() 或 setDay()
     */
    // 昨天
    /*
     * public static function yesterday($format = self::DEFAULT_DATE_FORMAT) {
     * return new XDateTime(date($format, time() - 3600 * 24)); }
     */
    // 前天
    public static function dayBeforeYesterday ($format = self::DEFAULT_DATE_FORMAT) {
        return new XDateTime(date($format, time() - 3600 * 24 * 2));
    }
    /*
     * deprecation end
     */

    public function getDate () {
        return $this->date;
    }

    public function getTime () {
        return strtotime($this->date);
    }

    // 由于储存限制，请不要在windows平台涉及到2037年以后的年份，freebsd平台未测试
    public function addYear ($year) {
        $yearNum = $this->getYear() + $year;
        return $this->setDate(self::YEAR, $yearNum);
    }

    public function addMonth ($month) {
        $MonthNum = $this->getMonth() + $month;
        $addYearNum = intval($MonthNum / 12);
        $dateTime = $this->addYear($addYearNum);
        $addMonthNum = intval($MonthNum % 12);
        return $dateTime->setDate(self::MONTH, $addMonthNum);
    }

    public function addDay ($day) {
        return $this->addSecond($day * (60 * 60 * 24));
    }

    public function addHour ($hour) {
        return $this->addSecond($hour * (60 * 60));
    }

    public function addMinute ($minute) {
        return $this->addSecond($minute * 60);
    }

    public function addSecond ($second) {
        $time = $this->getTime() + $second;
        return self::valueOf(date(self::DEFAULT_XDATETIME_FORMAT, $time));
    }

    public function setYear ($year) {
        return $this->setDate(self::YEAR, $year);
    }

    public function setMonth ($month) {
        return $this->setDate(self::MONTH, $month);
    }

    public function setDay ($day) {
        return $this->setDate(self::DAY, $day);
    }

    public function setHour ($hour) {
        return $this->setDate(self::HOUR, $hour);
    }

    public function setMinute ($minute) {
        return $this->setDate(self::MINUTE, $minute);
    }

    public function setSecond ($second) {
        return $this->setDate(self::SECOND, $second);
    }

    private function setDate ($format, $setNum) {
        $year = $this->getYear();
        $month = $this->getMonth();
        $day = $this->getDay();
        $hour = $this->getHour();
        $minute = $this->getMinute();
        $second = $this->getSecond();
        switch ($format) {
            case self::YEAR:
                $year = $setNum;
                break;
            case self::MONTH:
                $month = $setNum;
                break;
            case self::DAY:
                $day = $setNum;
                break;
            case self::HOUR:
                $hour = $setNum;
                break;
            case self::MINUTE:
                $minute = $setNum;
                break;
            case self::SECOND:
                $second = $setNum;
                break;
        }
        return self::createXDateTime($year, $month, $day, $hour, $minute, $second);
    }

    public function getYear () {
        return date("Y", $this->getTime());
    }

    public function getMonth () {
        return date("m", $this->getTime());
    }

    public function getDay () {
        return date("d", $this->getTime());
    }

    public function getHour () {
        return date("H", $this->getTime());
    }

    public function getMinute () {
        return date("i", $this->getTime());
    }

    public function getSecond () {
        return date("s", $this->getTime());
    }

    // 返回星期的数字值
    public function getW () {
        return date("w", $this->getTime());
    }

    // 获取给定时间那一周的周一的时间戳
    public static function getTheMondayBeginTime ($date = '') {

        if ($date == '') {
            $date = date('Y-m-d');
        }

        $time = strtotime($date);

        $wday = date("w", $time);
        $wday = ($wday == 0) ? 7 : $wday;

        return $time - ($wday - 1) * 24 * 60 * 60;
    }

    public static function yearDiff (XDateTime $d1, XDateTime $d2) {
        return ($d2->getYear() - $d1->getYear());
    }

    public static function monthDiff (XDateTime $d1, XDateTime $d2) {
        return self::yearDiff($d1, $d2) * 12 + ($d2->getMonth() - $d1->getMonth());
    }

    public static function dayDiff (XDateTime $d1, XDateTime $d2) {
        return ($d2->getTime() - $d1->getTime()) / (60 * 60 * 24);
    }

    public static function hourDiff (XDateTime $d1, XDateTime $d2) {
        return ($d2->getTime() - $d1->getTime()) / (60 * 60);
    }

    public static function minuteDiff (XDateTime $d1, XDateTime $d2) {
        return ($d2->getTime() - $d1->getTime()) / 60;
    }

    public static function secondDiff (XDateTime $d1, XDateTime $d2) {
        return ($d2->getTime() - $d1->getTime());
    }

    public static function addDaysFromNow ($days) {
        return new XDateTime(date(self::DEFAULT_XDateTime_FORMAT, time() + (int) $days * 86400));
    }

    public static function nowBetween ($beginDate, $endDate) {
        if (is_string($beginDate))
            $beginTime = strtotime($beginDate);
        if (is_string($endDate))
            $endTime = strtotime($endDate);
        $now = time();
        return ($now >= $beginTime && $now <= $endTime);
    }

    public static function afterToday ($date) {
        if (empty($date) || ($date == "")) {
            return true;
        }
        if (is_string($date))
            $date = strtotime($date);
        return $date > time();
    }

    public static function isToday ($date) {
        if (empty($date) || ($date == "")) {
            return true;
        }
        if (is_string($date))
            $date = strtotime($date);

        $today = self::today();
        $today = strtotime($today);
        return $date > $today;
    }

    public static function getYearsToToday ($from = 1949) {
        $years = array();
        $currentYear = (int) date('Y', time());
        for ($year = $from; $year <= $currentYear; $year ++) {
            $years[strval($year)] = strval($year);
        }
        return $years;
    }

    public function __toString () {
        return $this->date;
    }

    public function toString () {
        return $this->date;
    }

    public function toShortString () {
        return date(self::DEFAULT_DATE_FORMAT, $this->getTime());
    }

    public function toYmdHiStr () {
        return date("Y-m-d H:i", $this->getTime());
    }

    public function tomdHiStr () {
        return date("m-d H:i", $this->getTime());
    }

    public function tomdHStr () {
        return date("m-d H", $this->getTime());
    }

    public function equals ($otherXDateTime) {
        if (is_object($otherXDateTime) && (get_class($this) == get_class($otherXDateTime)))
            return ($this->getTime() == $otherXDateTime->getTime());
        return false;
    }

    public function hashCode () {
        $time = $this->getTime();
        return intval($time ^ intval($time >> 32));
    }

    public static function getNextMonthTodayYmd () {
        return XDateTime::getNow()->addMonth(1)->toShortString();
    }

    public static function get_chinese_weekday ($datetimestr) {
        return self::get_chinese_weekdayImp(strtotime($datetimestr));
    }

    public static function get_chinese_weekdayImp ($time) {
        $weekday = date('w', $time);
        $weekDefine = array(
            '日',
            '一',
            '二',
            '三',
            '四',
            '五',
            '六');

        return '周' . $weekDefine[$weekday];
    }

    public static function get_week_value ($datetimestr) {
        return date('w', strtotime($datetimestr));
    }

    // 获取上一周的起始数组
    public static function get_timespan_of_lastweek () {
        // $datenow = new DateTime();
        // $datenow->modify('+7 day')->format("Y-m-d");

        $weekday = date('w', time()); // 2 => 2 ;
        if ($weekday < 1) {
            $weekday = 7;
        }

        $offsetDay = 1 - ($weekday + 7);

        $todayBeginTime = strtotime(date("Y-m-d"));

        $begintime = $todayBeginTime + $offsetDay * 86400;
        $endtime = $begintime + 7 * 86400;

        $begintimestr = date("Y-m-d H:i:s", $begintime);
        $endtimestr = date("Y-m-d H:i:s", $endtime);

        return array(
            $begintimestr,
            $endtimestr);
    }

    // 日期字符串
    // 5月1日
    // 跨年显示年 2014年5月4日
    public static function getYnjDateStr ($timestr = "0000-00-00 00:00:00") {
        $theY = date("Y");
        $time = strtotime($timestr);
        $y = date("Y", $time);
        if ($y == $theY) {
            return date("n月j日", $time);
        }

        return date("Y年n月j日", $time);
    }

    // 时间字符串
    // 5月1日 12:35
    // 跨年显示年 2014年5月4日12时35分
    public static function getYnjHiTimeStr ($timestr = "0000-00-00 00:00:00") {
        $theY = date("Y");
        $time = strtotime($timestr);
        $y = date("Y", $time);
        if ($y == $theY) {
            return date("n月j日 H:i", $time);
        }

        return date("Y年n月j日 H:i", $time);
    }

    // 通过时间字符串计算相隔天数 by xuzhe

    public static function getDaySpan ($begin, $end) {
        $datetime1 = new \DateTime($begin);
        $datetime2 = new \DateTime($end);
        $interval = $datetime1->diff($datetime2);
        return intval($interval->format('%a'));
    }

    public static function getHourSpan ($begin, $end) {
        $datetime1 = strtotime($begin);
        $datetime2 = strtotime($end);
        $interval = $datetime2 - $datetime1;
        return floor($interval / (60 * 60));
    }

    public static function getYearSpan ($begin, $end) {
        $datetime1 = strtotime($begin);
        $datetime2 = strtotime($end);
        $interval = $datetime2 - $datetime1;
        return floor($interval / (60 * 60 * 24 * 365));
    }

    public static function getMonthSpan ($begin, $end) {
        $datetime1 = strtotime($begin);
        $datetime2 = strtotime($end);
        $interval = $datetime2 - $datetime1;
        return floor($interval / (60 * 60 * 24 * 30));
    }

    // 通过时间字符串和天数计算新的时间字符串

    public static function getNewDate ($date, $day) {
        if ($day >= 0) {
            $dateobj = new \DateTime($date);
            return $dateobj->modify("+{$day} day")->format("Y-m-d");
        } else {
            $day *= (- 1);
            $dateobj = new \DateTime($date);
            return $dateobj->modify("-{$day} day")->format("Y-m-d");
        }

    }

    // 通过时间字符串和小时数计算新的时间字符串

    public static function getNewHour ($date, $hour) {
        if ($hour >= 0) {
            $dateobj = new \DateTime($date);
            return $dateobj->modify("+{$hour} Hour")->format("Y-m-d H-i-s");
        } else {
            $hour *= (- 1);
            $dateobj = new \DateTime($date);
            return $dateobj->modify("-{$hour} Hour")->format("Y-m-d H-i-s");
        }

    }

    // 根据创建日期分组
    public static function GroupByCreateDate ($objects) {
        $arr = array();
        $datetmp = date("Y-m-d");

        foreach ($objects as $a) {
            if (self::changeTime2Date($a->createtime) == $datetmp) {
                $arr["$datetmp"][] = $a;
            } else {
                $datetmp = self::changeTime2Date($a->createtime);
                $arr["$datetmp"][] = $a;
            }
        }

        return $arr;
    }

    // 根据更新日期分组
    public static function GroupByUpdateDate ($objects) {
        $arr = array();
        $datetmp = date("Y-m-d");

        foreach ($objects as $a) {
            if (self::changeTime2Date($a->updatetime) == $datetmp) {
                $arr["$datetmp"][] = $a;
            } else {
                $datetmp = self::changeTime2Date($a->createtime);
                $arr["$datetmp"][] = $a;
            }
        }

        return $arr;
    }

    // 从时间中提取日期
    public static function changeTime2Date ($time) {
        $dateobj = new \DateTime($time);
        return $dateobj->format("Y-m-d");
    }

    // 按字符串型时间键值降序
    public static function ksortByTimeStr ($arr) {
        $tmparr = array();
        foreach ($arr as $time => $data) {
            $timetemp = strtotime($time);
            $tmparr["$timetemp"] = $data;
        }

        krsort($tmparr);
        $arr = array();

        foreach ($tmparr as $time => $data) {
            $timetemp = date("Y-m-d", $time);
            $arr["$timetemp"] = $data;
        }

        return $arr;
    }

    // TODO(liu) - 可以将步进和方向作为可选参数,以及性能上的改进
    public static function dateRange ($leftDateStr, $rightDateStr) {
        $leftDate = date_create($leftDateStr);
        $rightDate = date_create($rightDateStr);
        $dayDiff = date_diff($leftDate, $rightDate)->days;
        return array_map(
                function  ($diff) use( $leftDateStr) {
                    $datetmp = date_create($leftDateStr);
                    $datetmp->modify("+{$diff} days");
                    return $datetmp->format("Y-m-d");
                }, range(0, $dayDiff));
    }

    // 计算从2015-03-23至当日的周次
    public static function getWFromFirstDate ($thedate, $firstdate = '2015-03-23') {
        $time0 = strtotime($firstdate);
        $time1 = strtotime($thedate);

        return floor(($time1 - $time0) / (86400 * 7));
    }

    public static function getDatemdByWoy ($woy, $firstdate = '2015-03-23') {
        $time0 = strtotime($firstdate);
        $time1 = $time0 + $woy * 86400 * 7;
        return date('m-d', $time1);
    }

    public static function getDateYmdByWoy ($woy, $firstdate = '2015-03-23') {
        $time0 = strtotime($firstdate);
        $time1 = $time0 + $woy * 86400 * 7;
        return date('Y-m-d', $time1);
    }

    // 获取当前周起始日期[)区间,便于sql查询
    public static function theWeekRange ($basetime = "") {
        if (empty($basetime)) {
            $basetime = time();
        }
        $w = date('w', $basetime);
        $w = ($w == 0) ? 7 : $w;
        $l = 1 - $w;
        $r = 8 - $w;

        $ldate = date('Y-m-d', $basetime + 86400 * $l);
        $rdate = date('Y-m-d', $basetime + 86400 * $r);
        return array(
            $ldate,
            $rdate);
    }

    // 获取两个日期字符串之间的差值
    public static function getDateDiff ($leftDateStr, $rightDateStr) {
        $leftDate = date_create($leftDateStr);
        $rightDate = date_create($rightDateStr);
        return date_diff($leftDate, $rightDate)->days;
    }
    // 获取两个日期字符串之间的差值返回月份
    public static function getDateDiffOfMonth ($mindate, $maxdate) {
        $time1 = strtotime($mindate); // 这里是时间戳
        $time2 = strtotime($maxdate); // 时间2的时间戳

        $year1 = date("Y", $time1); // 时间1的年份
        $month1 = date("m", $time1); // 时间1的月份

        $year2 = date("Y", $time2); // 时间2的年份
        $month2 = date("m", $time2); // 时间2的月份

        return ($year2 * 12 + $month2) - ($year1 * 12 + $month1);
    }

    public static function getYearArrToNew($fromYear = "2015"){
        $arr = array();
        $nowYear = date("Y-01-01");
        $fromYear = date("Y-01-01", strtotime($fromYear."-01-01"));

        $fromtime = strtotime($fromYear); // 这里是传进来年的时间戳
        $nowtime = strtotime($nowYear); // 当前年时间的时间戳

        while($fromtime <= $nowtime){
            $year = date("Y", $fromtime);
            $arr[$year] = $year;
            $fromtime = strtotime("+1 years",$fromtime);
        }

        return $arr;
    }

    public static function getMonthArrByYear($year = "2015"){
        $arr = array();
        $date = $year."-01";

        for($i=0;$i<12;$i++){
            $arr[] = $date;
            $date = date("Y-m", strtotime("+1 month", strtotime($date)));
        }

        return $arr;
    }

}

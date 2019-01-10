<?php
namespace Xwork\xcommon;
/**
 * Config
 * @desc		配置项管理类,基本配置项见readme.txt
 * @remark		依赖类: 无
 * @copyright	(c)2012 xwork.
 * @file		Config.class.php
 * @author		shijianping <shijpcn@qq.com>
 * @date		2012-02-26
 */
class Config
{

    private static $configFile;

    private static $configCache;

    // 需要先设置好configFile
    public static function setConfigFile ($configFile) {
        self::$configFile = $configFile;
        self::loadConfig();
    }
    // 读取配置文件，初始化配置缓存
    private static function loadConfig () {
        if (empty(self::$configCache)) {
            if (! self::$configFile) {
                echo "empty config file";
                exit();
            }
            $config = include_once (self::$configFile);

            // $config 变量来源于 self::$configFile
            self::$configCache = $config;
        }
    }
    // 获得配置参数
    public static function getConfig ($key, $defaultValue = null) {
        self::loadConfig();
        $value = null;
        if (isset(self::$configCache[$key])) {
            $value = self::$configCache[$key];
        }

        if ($value === null) {
            $value = $defaultValue;
        }

        return $value;
    }
    // 设定配置参数
    public static function setConfig ($key, $value) {
        self::loadConfig();
        self::$configCache[$key] = $value;
    }
}

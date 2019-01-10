<?php
namespace Xwork\xmap;
use Xwork\xcommon\log\Log;
use Xwork\xmap\db\DbMgr;
use Xwork\xexception\SystemException;
use Xwork\xcommon\Config;
use Xwork\xmap\db\MysqlDataSource;
use Xwork\xmap\db\DbExecuter;
use Xwork\xmap\idgenerator\IDGeneratorByDb;

/**
 * BeanFinder
 * @desc		实例工厂,实例注册表
 * @remark		依赖类: 多个框架类
 * @copyright 	(c)2012 xwork.
 * @file		BeanFinder.class.php
 * @author		shijianping <shijpcn@qq.com>
 * @date		2012-02-26
 */
class BeanFinder
{
    // 工厂
    private static $factorys = array();
    // 实例缓存
    private static $interfaceArray = array();

    // 注册工厂
    public static function registerFactory ($factory) {
        self::$factorys[] = $factory;
    }
    // 注册实例
    public static function register ($interface, $object) {
        self::$interfaceArray[$interface] = $object;
    }
    // 清理工厂与实例缓存
    public static function clear () {
        self::$factorys = array();
        self::$interfaceArray = array();
    }
    // 判断工厂与实例缓存是否都为空，若是则返回真
    public static function isClear () {
        return empty(self::$factorys) && empty(self::$interfaceArray);
    }
    // 清除某种类型实例的缓存
    public static function clearBean ($typeOfBean) {
        self::$interfaceArray = array_diff_key(self::$interfaceArray, array(
            $typeOfBean => ""));
    }

    public static function getUnitOfWork() :? UnitOfWork {
        // 如果注册表里有则直接返回
        if (!array_key_exists('UnitOfWork', self::$interfaceArray)) {
            self::$interfaceArray["UnitOfWork"] = new UnitOfWork();
        }
        return self::$interfaceArray['UnitOfWork'];
    }

    public static function getTableNameCreator() :? TableNameCreator {
        // 如果注册表里有则直接返回
        if (!array_key_exists('TableNameCreator', self::$interfaceArray)) {
            self::$interfaceArray["TableNameCreator"] = new TableNameCreator();
        }
        return self::$interfaceArray['TableNameCreator'];
    }

    public static function getDbMgr() :? DbMgr {
        // 如果注册表里有则直接返回
        if (!array_key_exists('DbMgr', self::$interfaceArray)) {
            self::$interfaceArray["DbMgr"] = new DbMgr();
        }
        return self::$interfaceArray['DbMgr'];
    }

    public static function getDbExecuter($database='') :? DbExecuter {
        // 如果注册表里有则直接返回
        if (empty($database)) {
            $database = DaoBase::getDefaultDb();
        }
        $k = "DbExecuter{$database}";
        if (!array_key_exists($k, self::$interfaceArray)) {
            $dbe = self::getDbMgr()->getDbexecuter($database);
            self::$interfaceArray[$k] = $dbe;
        }
        return self::$interfaceArray[$k];
    }

    public static function getIDGenerator() :? IDGeneratorByDb {
        // 如果注册表里有则直接返回
        if (!array_key_exists('IDGenerator', self::$interfaceArray)) {
            self::$interfaceArray["IDGenerator"] = new IDGeneratorByDb();
        }
        return self::$interfaceArray['IDGenerator'];
    }

    // 手工启动数据库链接
    public static function setupManual_Db ($host, $database, $username, $password) {
        if (self::isClear()) {
            $dataSource = new MysqlDataSource($host, $database, $username, $password);
            self::register("DbExecuter{$database}", new DbExecuter($dataSource, $database));
            self::register("IDGenerator", new IDGeneratorByDb());
        }
    }
}

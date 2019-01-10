<?php

namespace Xwork\xmap;

use Xwork\xcommon\DBC;
use Xwork\xcommon\log\Log;
use Xwork\xmap\db\DbExecuter;

/**
 * DaoBase
 * @desc         数据库访问对象基类
 * @remark         依赖类: Entity , XEntity , Debug , BeanFinder , DBC , Config , DbExecuter
 * @copyright    (c)2012 xwork.
 * @file         DaoBase.class.php
 * @author        shijianping <shijpcn@qq.com>
 * @date         2012-02-26
 */
abstract class DaoBase
{

    // 默认数据库名称
    private static $defaultDb = '';

    // 带命名空间的实体类名称，如Board, User
    protected $entityClassName;

    // 数据库名称,如果为空则读中心库(缺省库)
    protected $database;

    // 散列表的编号
    protected $tableno = 0;

    protected $pkeyName = 'id';

    // 缓存,提高性能
    /**
     * @var DbExecuter|null
     */
    protected $_dbExecuter;

    /*
     * 构造函数，传递实体名与散列表编号（默认为0） 现在可以支持多数据库了 1. 缺省情况,读写缺省库 2.
     * 如果一个实体存在一个固定的其他库里,则可以通过继承Dao来实现,或传参数 3. 如果是库散列的情况,则需要创建Dao时动态传$database参数
     */
    public function __construct($entityClassName, $dbconf = array()) {
        $tableno = 0;
        $database = '';
        $pkeyName = 'id';

        // 向前兼容,这个参数曾经名称为 $tableno
        if (is_numeric($dbconf) && $dbconf) {
            $tableno = $dbconf; // 数字 : 表暂列后缀
        } elseif (is_string($dbconf) && $dbconf) {
            $database = $dbconf; // 字符串 : 库名
        } elseif (is_array($dbconf)) {
            if (isset($dbconf['tableno']) && $dbconf['tableno']) {
                $tableno = $dbconf['tableno'];
            }
            if (isset($dbconf['database']) && $dbconf['database']) {
                $database = $dbconf['database'];
            }
            if (isset($dbconf['pkeyName']) && $dbconf['pkeyName']) {
                $pkeyName = $dbconf['pkeyName'];
            }
            if (isset($dbconf['pkeyname']) && $dbconf['pkeyname']) {
                $pkeyName = $dbconf['pkeyname'];
            }
        }

        // 修正 $database
        $database = $database ? $database : DaoBase::getDefaultDb();

        //这里是为了解决表动态外键objtype命名空间问题
        if (strstr($entityClassName, '\\') === false) {
            $entityClassName = '\\' . $entityClassName;
        }
        $this->entityClassName = $entityClassName;
        $this->tableno = $tableno;
        $this->database = $database;
        $this->pkeyName = $pkeyName;

        $this->_dbExecuter = BeanFinder::getDbExecuter($database);
    }

    abstract protected function row2Object($row);

    // 自举，配置了cache
    protected final function getById($id, $needDBC = true) {
        $logkey = "{$this->database},{$this->entityClassName},{$id},{$needDBC}";

        // null, 暂时容忍的, 可以延迟修复
        if (is_null($id)) {
            Log::warn("[-- {$this->entityClassName}::getById [{$id}] is_null --]");
            return null;
        }

        // 空字符串, 暂时容忍的, 可以延迟修复
        if ($id === '') {
            Log::warn("[-- {$this->entityClassName}::getById [{$id}] is empty str --]");
            return null;
        }

        // 非数字的情况, 如 'abc', array 等, 需要立刻修复
        if ($needDBC) {
            DBC::requireTrue(is_numeric($id), "[-- {$this->entityClassName}::getById [{$id}] not is_numeric --]", -1, "{$this->entityClassName}::getById [{$id}] not is_numeric");
        }

        // 0, 正常情况
        if ($id < 1) {
            return null;
        }

        // 从缓存里取一下试试
        $unitOfWork = BeanFinder::getUnitOfWork();
        $entity = $unitOfWork->getEntity($id, $this->entityClassName, $this->database);
        if ($entity instanceof EntityBase) {
            return $entity;
        }

        $ids = array();

        // 在不散列的情况下，进行ids合并
        if ($this->tableno < 1) {
            $ids = $unitOfWork->getMaybeIds($this->entityClassName, $this->database);
            $unitOfWork->unsetMaybeIds($this->entityClassName, $this->database);
        }

        // 不散列或者只有一个id需求时
        if (empty($ids)) {
            $tableName = $this->getTableName();
            $sql = "select * from {$tableName} where {$this->pkeyName}=:id ";
            $entity = $this->loadBySimpleType($sql, array(
                ":id" => $id));

            if (empty($entity)) {
                // TODO 应该是 warn
                Log::sys("DaoBase::getById[$logkey][fail][1]");
            }

            return $entity;
        }

        // ids合并
        $ids[$id] = $id;

        // 合并后,如果还是只有一个id
        if (count($ids) == 1) {
            $tableName = $this->getTableName();
            $sql = "select * from {$tableName} where {$this->pkeyName}=:id ";
            $entity = $this->loadBySimpleType($sql, array(
                ":id" => $id));

            if (empty($entity)) {
                // TODO 应该是 warn
                Log::sys("DaoBase::getById[$logkey][fail][2]");
            }

            return $entity;
        }

        // maybeIds 加载
        $entitys = $this->getArrayByIds($ids);

        // 前面的逻辑必须保证实体已经被放进了loadMap
        $entity = $unitOfWork->getEntity($id, $this->entityClassName, $this->database);

        if (empty($entity)) {
            // TODO 应该是 warn
            Log::sys("DaoBase::getById[$logkey][fail][3]");
        }

        return $entity;
    }

    // 通过id数组获得实体数组
    protected final function getArrayByIds($ids) {
        if (empty($ids)) {
            return array();
        }

        $inIds = array();

        // 先从缓存里取
        $unitOfWork = BeanFinder::getUnitOfWork();
        foreach ($ids as $id) {
            if (false == is_numeric($id)) {
                continue;
            }

            $entity = $unitOfWork->getEntity($id, $this->entityClassName, $this->database);
            if (empty($entity)) {
                $inIds[] = $id;
            }
        }

        if (count($inIds) > 0) {
            $cond = "and `{$this->pkeyName}` in(";
            $cond .= implode(",", $inIds);
            $cond .= ")";

            $tableName = $this->getTableName();
            $sql = "select * from {$tableName} where 1=1 {$cond}";
            $entitys = $this->loadArrayBySimpleType($sql); // 仅查询未缓存的实体
        }

        $entitys = $this->combinArray($ids); // 通过combinArray可以获得按照id排序的实体

        return $entitys;
    }

    // 通过id数组获得实体数组,应该在所有需查实体都缓存在工作单元时使用
    protected final function combinArray($ids) {
        $entitys = array();
        foreach ($ids as $id) {
            $entity = $this->getById($id, false);
            if (!empty($entity)) {
                $entitys[] = $entity;
            }
        }

        return $entitys;
    }

    // 通过条件查询单个实体
    protected final function getByCond($cond, $bind = array()) {
        $tableName = $this->getTableName();
        $sql = "select * from {$tableName} where 1=1 {$cond}";
        return $this->load($sql, $bind);
    }

    // 通过条件查询实体数组
    protected final function getArrayByCond($cond = "", $bind = array()) {
        $tableName = $this->getTableName();
        $sql = "select * from {$tableName} where 1=1 {$cond}";
        return $this->loadArray($sql, $bind);
    }

    // 通过条件查询实体数组，带翻页接口
    protected final function getArrayByCond4Page($cond, $pagesize, $pagenum, $bind = array()) {
        DBC::requireTrue(is_numeric($pagesize), "$pagesize not is number");
        DBC::requireTrue(is_numeric($pagenum), "$pagenum not is number");
        $tableName = $this->getTableName();
        $sql = "select * from {$tableName} where 1=1 {$cond} ";
        return $this->loadArray4Page($sql, $pagesize, $pagenum, $bind);
    }

    // 获得计数sql语句的前半部分
    protected final function getCountSqlOfCond($cond) {
        $tableName = $this->getTableName();
        return "select count(*) from {$tableName} where 1=1 {$cond} ";
    }

    protected final function load($sql, $bind = array()) {
        return $this->loadBySimpleType($sql, $bind);
    }

    // 查询单个实体,通过完整的sql语句,简单的方式,不检查loadMap2，不检查cache,承担了getById的出口
    protected final function loadBySimpleType($sql, $bind = array()) {
        // 修正sql 补 limit 1
        $str1 = stristr($sql, "limit");
        $str2 = stristr($sql, ";");
        if (empty($str1) && empty($str2)) {
            $sql .= " limit 1 ";
        }

        $rs = $this->_dbExecuter->query($sql, $bind);
        if (empty($rs)) {
            return null;
        }

        return $this->row2Object($rs[0]);
    }

    protected final function loadArray($sql, $bind = array()) {
        return $this->loadArrayBySimpleType($sql, $bind);
    }

    // 查询实体数组,通过完整的sql语句,简单的方式,不检查loadMap2，不检查cache,承担了getArrayByIds的出口
    protected final function loadArrayBySimpleType($sql, $bind = array()) {
        $arrayEntities = array();
        $rs = $this->_dbExecuter->query($sql, $bind);
        foreach ($rs as $row) {
            $arrayEntities[] = $this->row2Object($row);
        }
        return $arrayEntities;
    }

    // 通过完整的sql语句查询实体数组, 带翻页功能
    protected final function loadArray4Page($sql, $pagesize, $pagenum, $bind = array()) {
        $offset = ($pagenum - 1) * $pagesize;
        $sql = DbExecuter::limit($sql, $offset, $pagesize);
        return $this->loadArray($sql, $bind);
    }

    // /////////////////////////////////////////////////////////////
    // 通用方法-代替-DbExecuter
    // queryValue 单值
    // queryValues 单列
    // queryRow 单行
    // queryRows 多行多列
    // executeNoQuery 非查询

    // 返回单值, 没有cache
    public function queryValue($sql, $bind = array(), $database = "") {
        return BeanFinder::getDbExecuter($database)->queryValue($sql, $bind);
    }

    // 返回单列, 没有cache
    public function queryValues($sql, $bind = array(), $database = "") {
        return BeanFinder::getDbExecuter($database)->queryValues($sql, $bind);
    }

    // 返回单行, 没有cache
    public function queryRow($sql, $bind = array(), $database = "") {
        $rows = BeanFinder::getDbExecuter($database)->query($sql, $bind);
        if (is_array($rows) && is_array($rows[0])) {
            return $rows[0];
        } else {
            return array();
        }
    }

    // 返回多行, 没有cache
    public function queryRows($sql, $bind = array(), $database = "") {
        return BeanFinder::getDbExecuter($database)->query($sql, $bind);
    }

    // 执行非查询语句，不存在有没有cache
    public function executeNoQuery($sql, $bind = array(), $database = "") {
        return BeanFinder::getDbExecuter($database)->executeNoQuery($sql, $bind);
    }

    // 通过sql语句查询第一行第一列的值，中间经过sql缓存
    // 需要调用者保证语句的正确性，主要是为了查询id，同时加cache
    public function queryValueWithCache($sql, $bind = array(), $expireTime = 0, $database = "") {
        // $sql_key = $sql."+".serialize($bind);
        $sql_key = $echoSql = DbExecuter::buildSql($sql, $bind);
        $str = $sql_key = "[queryValueWithCache][beg][$database] -- $sql_key";
        Log::sys($str);
        $hit = "1";

        $unitOfWork = BeanFinder::getUnitOfWork();
        $value = $unitOfWork->getQueryRet($sql_key);
        if (empty($value)) {
            $hit = "0";
            $value = BeanFinder::getDbExecuter($database)->queryValue($sql, $bind);
            $unitOfWork->registerQueryRet($sql_key, $value);
        }

        $setfix = "";
        if ($hit == "0" && !empty($value)) {
            $setfix = "and set value ";
        }

        $str = "[queryValueWithCache][end][$database] hit=$hit $setfix--";
        Log::sys($str);
        return $value;
    }

    // 通过sql语句查询第一列的值，中间经过sql缓存
    // 需要调用者保证语句的正确性，主要是为了查询ids，同时加cache
    public function queryValuesWithCache($sql, $bind = array(), $database = "") {
        // $sql_key = $sql."+".serialize($bind);
        $sql_key = $echoSql = DbExecuter::buildSql($sql, $bind);
        $str = $sql_key = "[queryValuesWithCache][beg][$database] -- $sql_key";
        Log::sys($str);
        $hit = "1";

        $unitOfWork = BeanFinder::getUnitOfWork();
        $values = $unitOfWork->getQueryRet($sql_key);
        if (empty($values)) {
            $hit = "0";
            $values = BeanFinder::getDbExecuter($database)->queryValues($sql, $bind);
            $unitOfWork->registerQueryRet($sql_key, $values);
        }

        $setfix = "";
        if ($hit == "0" && !empty($values)) {
            $setfix = "and set values ";
        }

        $str = "[queryValuesWithCache][end][$database] hit=$hit $setfix--";
        Log::sys($str);
        return $values;
    }

    // 通过sql语句查询第一行，中间经过sql缓存
    // 需要调用者保证语句的正确性，同时加cache
    public function queryRowWithCache($sql, $bind = array(), $database = "") {
        $sql_key = $echoSql = DbExecuter::buildSql($sql, $bind);
        $str = $sql_key = "[queryRowWithCache][beg][$database] -- $sql_key";
        Log::sys($str);
        $hit = "1";

        $unitOfWork = BeanFinder::getUnitOfWork();
        $value = $unitOfWork->getQueryRet($sql_key);
        if (empty($value)) {
            $hit = "0";
            $rows = BeanFinder::getDbExecuter($database)->query($sql, $bind);
            if (is_array($rows) && is_array($rows[0])) {
                $value = $rows[0];
            } else {
                $value = array();
            }
            $unitOfWork->registerQueryRet($sql_key, $value);
        }

        $setfix = "";
        if ($hit == "0" && !empty($value)) {
            $setfix = "and set value ";
        }

        $str = "[queryRowWithCache][end][$database] hit=$hit $setfix--";
        Log::sys($str);
        return $value;
    }

    // 通过sql语句查询多行，中间经过sql缓存
    // 需要调用者保证语句的正确性，同时加cache
    public function queryRowsWithCache($sql, $bind = array(), $database = "") {
        $sql_key = $echoSql = DbExecuter::buildSql($sql, $bind);
        $str = $sql_key = "[queryRowsWithCache][beg][$database] -- $sql_key";
        Log::sys($str);
        $hit = "1";

        $unitOfWork = BeanFinder::getUnitOfWork();
        $value = $unitOfWork->getQueryRet($sql_key);
        if (empty($value)) {
            $hit = "0";
            $value = $rows = BeanFinder::getDbExecuter($database)->query($sql, $bind);

            $unitOfWork->registerQueryRet($sql_key, $value);
        }

        $setfix = "";
        if ($hit == "0" && !empty($value)) {
            $setfix = "and set value ";
        }

        $str = "[queryRowsWithCache][end][$database] hit=$hit $setfix--";
        Log::sys($str);
        return $value;
    }

    // 获取表名
    public function getTableName() {
        return self::getTableNameImp($this->entityClassName, $this->tableno, $this->database);
    }

    // 初始化,给外部修改的接口
    public static function initDefaultDb($database = '') {
        self::$defaultDb = $database;
    }

    // 获取默认数据库
    public static function getDefaultDb() {
        return self::$defaultDb;
    }

    // 获取表名
    public static function getTableNameImp($entityClassName, $tableno = 0, $database = '') {
        $tableNameCreator = BeanFinder::getTableNameCreator();
        return $tableNameCreator->getTableName($entityClassName, $tableno, $database);
    }
}

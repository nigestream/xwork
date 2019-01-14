<?php

namespace Xwork\xmap;

use Xwork\xcommon\XUtility;
use Xwork\xcommon\DBC;
use Xwork\xmvc\XContext;
use Xwork\xcommon\Debug;

/**
 * EntityBase
 * @desc        实体对象基类,可继承,具有belongto功能
 * @remark        依赖类: BeanFinder , UnitOfWork , Dao , DBC
 * @copyright    (c)2012 xwork.
 * @file        EntityBase.class.php
 * @author        shijianping <shijpcn@qq.com>
 * @date        2012-02-26
 */
class EntityBase
{

    // 是否需要构造函数字段检查
    public static $needConstructCheckCount = 0;

    // 所属数据库
    protected $database = "_defaultdb_";

    // 对象散列表编号
    protected $tableno;

    // 实体属性字段
    protected $_keys;

    // 只读字段
    protected $_keys_lock;

    // 废弃字段
    protected $_keys_abandon;

    // 外键关系
    protected $_belongtos;

    // 实际数据
    protected $_cols;

    // 修正字段
    protected $_colsfix;

    // 对象id ,对应表主键 , 如果主键不是id 在保存时框架会转化为主键名, 暂不支持联合主键
    protected $id;

    // 是否新建实体
    protected $isNewEntity = false;

    // 是否删除
    protected $removed = false;

    // 是否只读
    protected $isReadOnly = false;

    // 已经脏了的字段
    protected $dirtyKeys = array();

    // 初始化所属数据库, 子类可以继承
    protected function init_database() {
        $defaultdb = DaoBase::getDefaultDb();
        $this->database = $defaultdb ? $defaultdb : '_defaultdb_';
    }

    // 初始化实体字段 $_keys
    protected function init_keys() {
        throw new \Exception("init_keys need override");
    }

    // 初始化实体只读字段 $_keys_lock
    protected function init_keys_lock() {
        $this->_keys_lock = array();
    }

    // 初始化实体废弃字段 $_keys_abandon
    protected function init_keys_abandon() {
        $this->_keys_abandon = array();
    }

    // 初始化belongtos 类似于: $this->_belongtos["channel"] =
    // array("type"=>"Channel","key"=>"channelid","tableno"=>0);
    // hasone关系也可以定义在此数组中
    // 数组的key约定为全小写，如 channel
    protected function init_belongtos() {
        $this->_belongtos = array();
    }

    protected function _getHook($key) {
        return "nothing";
    }

    public function getBelongtos() {
        return $this->_belongtos;
    }

    public function get_keys() {
        return $this->_keys;
    }

    public function get_cols() {
        return $this->_cols;
    }

    // 实体是否需要记录变更历史
    public function notXObjLog() {
        return false;
    }

    //
    public function getPrimaryId() {
        return $this->id;
    }

    // 表主键
    public function getPkeyname() {
        return "id";
    }

    // 获取62编码的id;
    public function getBigId() {
        return XUtility::dec2any($this->id);
    }

    public function getTableNo() {
        return $this->tableno;
    }

    // 穿透获取
    public function getCol($key) {
        return $this->_cols[$key];
    }


    public function in_keys($value) {
        $value = strtolower($value);
        return in_array($value, $this->_keys);
    }

    public function in_keys_lock($value) {
        $value = strtolower($value);
        return in_array($value, $this->_keys_lock);
    }

    public function __construct(array $row, $id = 0, $isNewEntity = false, $dbconf = array()) {
        $this->init_database();
        $this->init_keys();
        $this->init_keys_lock();
        $this->init_keys_abandon();

        // 数组value转小写

        $this->_keys = XUtility::arrayStr2Lower($this->_keys);
        $this->_keys_lock = XUtility::arrayStr2Lower($this->_keys_lock);
        $this->_keys_abandon = XUtility::arrayStr2Lower($this->_keys_abandon);

        // 数组key转小写
        $row = array_change_key_case($row, CASE_LOWER);

        $tableno = 0;
        $database = $this->database;

        // 向前兼容,这个参数曾经名称为 $tableno
        if (is_numeric($dbconf) && $dbconf) {
            $tableno = $dbconf; // sjp: 该不该再支持?
        } elseif (is_string($dbconf) && $dbconf) {
            $database = $dbconf; // sjp: 该不该再支持?
        } elseif (is_array($dbconf)) {
            if (isset($dbconf['tableno']) && $dbconf['tableno']) {
                $tableno = $dbconf['tableno'];
            }
            if (isset($dbconf['database']) && $dbconf['database']) {
                $database = $dbconf['database'];
            }
        }

        // 主键字段名
        $pkeyName = $this->getPkeyname();

        // id补丁，导入旧数据或想手工控制id值时使用
        if (isset($row[$pkeyName]) && $row[$pkeyName] > 0 && $id <= 0) {
            $id = $row[$pkeyName];
        }

        // id没值肯定是isNewEntity,否则不一定
        if ($id == 0) {
            $this->id = self::createID();
            $isNewEntity = true;
        } else {
            $this->id = $id;
        }

        // 反写一次id
        $this->tableno = $tableno;
        $this->isNewEntity = $isNewEntity;
        $this->removed = false;
        $this->database = $database;

        $this->_cols = array();
        $this->_colsfix = array();

        // 检查,和赋值分离,数据库加载的不需要检查
        if (self::$needConstructCheckCount <= 0) {
            foreach ($this->_keys as $key) {
                $class = get_called_class();
                DBC::requireNotNull($row[$key], "{$class}::__construct failed $key = null");
            }
        }

        // 赋值
        foreach ($this->_keys as $key) {
            $this->_cols[$key] = $row[$key];
        }

        // 转小写,其实上面已经转换一次了
        $this->_cols = array_change_key_case($this->_cols, CASE_LOWER);

        // 延迟初始化,有可能需要用到其他字段的值
        $this->init_belongtos();
        // 数组key转小写
        $this->_belongtos = array_change_key_case($this->_belongtos, CASE_LOWER);

        // 注册实体
        BeanFinder::getUnitOfWork()->registerEntity($this);
    }

    // 统一数据获取接口，如果键名错误，在开发环境中报错
    public function __get($key) {
        $key = strtolower($key);
        switch ($key) {
            case "id":
                return $this->id;
            case "tableno":
                return $this->tableno;
        }

        // 有效属性
        if (in_array($key, $this->_keys)) {
            return $this->_cols[$key];
        }

        // 修正属性
        if (isset($this->_colsfix[$key])) {
            return $this->_colsfix[$key];
        }

        // 留个hook,让子类继承
        $obj = $this->_getHook($key);
        if ($obj !== "nothing") {
            return $obj;
        }

        $belongto = [];

        // 检查belongto关系中是否存在此key
        $belongto = $this->_belongtos[$key] ?? [];
        if (!empty($belongto)) {
            $tableno = 0;
            $database = '';
            $pkeyName = 'id';
            $daotype = 'Dao';
            if (isset($belongto['tableno']) && $belongto['tableno']) {
                $tableno = $belongto['tableno'];
            }
            if (isset($belongto['database']) && $belongto['database']) {
                $database = $belongto['database'];
            }
            if (isset($belongto['pkeyName']) && $belongto['pkeyName']) {
                $pkeyName = $belongto['pkeyName'];
            }
            if (isset($belongto['pkeyname']) && $belongto['pkeyname']) {
                $pkeyName = $belongto['pkeyname'];
            }

            $dbconf = array();
            $dbconf['tableno'] = $tableno;
            $dbconf['database'] = $database;
            $dbconf['pkeyName'] = $pkeyName;

            $daotype = "\\Xwork\\xmap\\$daotype";
            $dao = new $daotype($belongto["type"], $dbconf);
            $idstr = $belongto["key"];

            return $dao->getEntityById($this->$idstr); // 可能为空
        }

        DBC::requireTrue(in_array($key, $this->_keys), "$key not in _keys", -1, "$key not in _keys");
    }

    // 统一数据设置接口
    public function __set($key, $value) {
        $key = strtolower($key);

        DBC::requireTrue(BeanFinder::getUnitOfWork()->isReadOnly() == false, "data isReadOnly, {$this->getClassName()}::$key = $value");
        DBC::requireTrue(BeanFinder::getUnitOfWork()->isCommited() == false, "data isCommited, {$this->getClassName()}::$key = $value");

        // 尝试重新注册loadMap
        BeanFinder::getUnitOfWork()->tryReRegisterIfNeed($this);

        // 重新加载
        $this->tryReload();

        $method = "set$key";
        if (method_exists($this, $method)) {
            return $this->$method($value);
        }

        // DBC::requireTrue(in_array($key, $this->_keys), "$key not in _keys");
        if (isset($this->_keys_lock)) {
            DBC::requireTrue(!in_array($key, $this->_keys_lock), "$key in _keys_lock", -1, "$key in _keys_lock");
        }

        DBC::requireNotNull($value, "$key = null");

        // 有效属性修改
        if (in_array($key, $this->_keys)) {
            return $this->_cols[$key] = $value;
        }

        // 修正属性赋值
        return $this->_colsfix[$key] = $value;
    }

    // 对锁定的字段进行修改,或者跳过 __set 检查
    public function set4lock($key, $value) {

        // 尝试重新注册loadMap
        BeanFinder::getUnitOfWork()->tryReRegisterIfNeed($this);

        // 重新加载
        $this->tryReload();

        $this->_cols[$key] = $value;
    }

    // 需要覆写
    public function tryReload() {
    }

    public function __toString() {
        $str = '';
        $str .= "\nid => {$this->id}";
        $str .= "\nversion => {$this->version}";
        $str .= "\ncreatetime => {$this->createtime}";
        $str .= "\nupdatetime => {$this->updatetime}";
        foreach ($this->_cols as $k => $v) {
            $str .= "\n$k => $v";
        }
        return trim($str);
    }

    // 从实体生成完整的数据数组,主要用于日志信息
    public function toFullArray() {
        $arr = array();
        $arr['id'] = $this->id;
        $arr['version'] = $this->version;
        $arr['createtime'] = $this->createtime;
        $arr['updatetime'] = $this->updatetime;

        $arr += $this->_cols;

        return $arr;
    }

    // 从实体生成数组，在需要时覆写
    public function toArray() {
        $pkeyName = $this->getPkeyname();
        return array_merge(array(
            $pkeyName => $this->id), $this->_cols);
    }

    // 合并 _cols 和 _colsfix
    public function toJsonArray() {
        $arr = array();
        $arr += $this->toFullArray();
        $arr += $this->_colsfix;

        return $arr;
    }

    // 可以覆写
    public function toIphoneArray() {
        return $this->toArray();
    }

    // 可以覆写
    public function toIphoneArray4Detail() {
        return array(
            "nodata" => 1);
    }

    // m1
    public function getM1() {
        return substr(md5(json_encode($this->toIphoneArray())), 0, 2);
    }

    // m2
    public function getM2() {
        return substr(md5(json_encode($this->toIphoneArray4Detail())), 0, 2);
    }

    // 是否只读实体
    public function isReadOnly() {
        return $this->isReadOnly;
    }

    // 检查实体是否已经被删除
    public function setReadOnly($isReadOnly = true) {
        return $this->isReadOnly = $isReadOnly;
    }

    // 是否新建,Entity类如果继承EntityBase则需要覆写
    public function isCreated() {
        return $this->isNewEntity;
    }

    // 获取数据库名
    public function getDatabaseName() {
        if ($this->database == "_defaultdb_") {
            return "";
        } else {
            return $this->database;
        }
    }

    // 检查实体是否已经被删除
    public function isRemoved() {
        return $this->removed;
    }

    // 实体删除函数，推荐使用，可以通过覆写扩展功能
    public function remove() {
        DBC::requireTrue(BeanFinder::getUnitOfWork()->isReadOnly() == false, "data isReadOnly , can't remove", -1, "data isReadOnly , can't remove");
        DBC::requireTrue(BeanFinder::getUnitOfWork()->isCommited() == false, "data isCommited , can't remove", -1, "data isCommited , can't remove");

        // 尝试注册
        BeanFinder::getUnitOfWork()->tryReRegisterIfNeed($this);

        $this->removed = true;
    }

    // 回收，不删除
    public function unRemove() {
        $this->removed = false;
    }

    // 获取类名,补充命名空间前缀
    public function getClassName() {
        return '\\' . get_class($this);
    }

    // 如果Entity 继承自EntityBase 则需要覆写,做版本检查,并修改 currentVersion 和 updatetime
    public function setDirty() {
        return $this;
    }

    public function setDirtyKeys($dirtyKeys) {
        $this->dirtyKeys = $dirtyKeys;
    }

    public function getDirtyKeys() {
        return $this->dirtyKeys;
    }

    public function setCreated() {
        DBC::requireTrue($this->isNewEntity, "not new Entity");
        $this->isNewEntity = false;
        return $this;
    }

    // 生成insert语句时，可以增加一些辅助的功能，比如：全文索引数据，辅助表数据等，Msg是个典型的例子
    public function getInsertSqlsFix() {
        return array();
    }

    // 生成Update语句时，可以增加一些辅助的功能
    public function getUpdateSqlsFix() {
        return array();
    }

    // 生成Delete语句时，可以增加一些辅助的功能,相同功能也可以通过覆写
    // remove方法，在里面直接进行删除调用，但那样这个语句的执行将会早于工作单元的统一提交，不利于事务管理,
    // 同时，remove中更多的是生成级联实体对象，并调用相应的remove,而 getDeleteSqlsFix 更多是直接生成批量删除的脚本 和
    // 非实体表数据删除脚本
    public function getDeleteSqlsFix() {
        return array();
    }

    // 生成实体ID
    public static function createID() {
        $idGen = BeanFinder::getIDGenerator();
        return $idGen->getNextID();
    }

    //获取表名，给sql查询用
    public static function getTableName($entityClassName = "", $tableno = 0, $datbaseName = '') {
        if (empty($entityClassName)) {
            $entityClassName = get_called_class();
        }
        return DaoBase::getTableNameImp($entityClassName, $tableno, $datbaseName);
    }
}

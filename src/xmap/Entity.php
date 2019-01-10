<?php

namespace Xwork\xmap;

use Xwork\xcommon\Config;
use Xwork\xcommon\DBC;
use Xwork\xcommon\log\Log;
use Xwork\xcommon\XDateTime;

/**
 * Entity
 * @desc        实体基类
 * @remark        依赖类: DBC , BeanFinder , UnitOfWork , DBC , DbExecuter , Dao , IDGenerator , Config
 * @copyright    (c)2012 xwork.
 * @file        Entity.class.php
 * @author        shijianping <shijpcn@qq.com>
 * @date        2012-02-26
 */
class Entity extends EntityBase
{

    const TABLENO = 0;

    // 初始版本
    // TODO by shijp 2011-09-13 是否做构造检查的计数器
    const INITIALIZED_VERSION = -9527;

    // 原版本号
    protected $originalVersion;

    // 当前版本号
    protected $currentVersion;

    // 实体创建时间
    protected $createtime;

    // 实体更新时间
    protected $updatetime;

    // 构造函数，构造函数是创建实体的唯一方法，一般由createByBiz()函数调用
    public function __construct($row, $dbconf = array(), $id = 0, $version = -9527, $createtime = null, $updatetime = null) {
        if ($id == 0) {
            DBC::requireTrue($version == self::INITIALIZED_VERSION, "create entity failed!");
        }

        $isNewEntity = false;
        if ($version == self::INITIALIZED_VERSION) {
            $this->originalVersion = $version;
            $this->currentVersion = 1;
            $isNewEntity = true;
        } else {
            $this->originalVersion = $version;
            $this->currentVersion = $version;
        }

        if (empty($createtime) && isset($row['createtime'])) {
            $createtime = $row['createtime'];
        }

        if (empty($updatetime) && isset($row['updatetime'])) {
            $updatetime = $row['updatetime'];
        }

        $this->createtime = $createtime == null ? date('Y-m-d H:i:s', time()) : $createtime;
        $this->updatetime = $updatetime == null ? date('Y-m-d H:i:s', time()) : $updatetime;

        parent::__construct($row, $id, $isNewEntity, $dbconf);
    }

    // 获取entity特有值
    public function _getHook($key) {
        switch ($key) {
            case "version":
                return $this->currentVersion;
            case "createtime":
                return $this->createtime;
            case "updatetime":
                return $this->updatetime;
            case "originalversion":
                return $this->originalVersion;
            case "originalVersion":
                return $this->originalVersion;
        }

        return parent::_getHook($key);
    }

    // 重置原始版本号
    public function resetOriginalVersion() {
        $this->originalVersion = $this->currentVersion;
    }

    // TODO by sjp:基类的应该够用了
    public function isCreated() {
        return $this->originalVersion == self::INITIALIZED_VERSION;
    }

    public function setDirty() {
        if ($this->currentVersion != $this->originalVersion) {
            throw new \Exception("can not set dirty [" . $this->getClassName() . "][{$this->id}]");
        }

        $this->currentVersion = $this->originalVersion + 1;
        $this->updatetime = date('Y-m-d H:i:s', time());

        return $this;
    }

    public function setDirtyKeys($dirtyKeys) {
        $dirtyKeys["version"] = "version";
        $dirtyKeys["updatetime"] = "updatetime";
        $this->dirtyKeys = $dirtyKeys;
    }

    public function setCreated() {
        if ($this->currentVersion != 1 || $this->originalVersion != self::INITIALIZED_VERSION) {
            throw new \Exception('can not set created! currentVersion=' . $this->currentVersion . " ; originalVersion=" . $this->originalVersion);
        }

        $this->originalVersion = 1;
        return $this;
    }

    // /////////////////////////////////
    // tryReload 实验 TODO by sjp 20100326
    public function tryReload() {

        // 新对象不进行reload
        if ($this->isCreated()) {
            return;
        }

        $dbExecuter = BeanFinder::getDbExecuter($this->getDatabaseName());
        $unitOfWork = BeanFinder::getUnitOfWork();

        // reload 除了主从问题还可以解决memcached没有同步更新的问题
        // 没有更新快照带来的可能是多一次update操作,但带来的好处就是memcache被同步清理
        // fix 2011-12-27 条件修改了一下
        if ($unitOfWork->needReload($this) && false == Config::getConfig("closereload") && true == $dbExecuter->isRwSplit()) {
            $logstr = "[-- reloadEntity " . $this->getClassName() . " : " . $this->id . " --]";
            Log::warn($logstr);

            $tableName = self::getTableName($this->getClassName());

            // 改为读主库
            $dbExecuter->saveNeedRwSplit();
            $dbExecuter->unNeedRwSplit();
            $pkeyName = $this->getPkeyname();

            $rows = $dbExecuter->query("select * from {$tableName} where {$pkeyName}=" . $this->id);
            $row = $rows[0];

            // 恢复读写分离状态
            $dbExecuter->restoreRwSplit();

            // 标记为重新加载过一次了
            $unitOfWork->reloadOneTime($this);

            // 告警
            if ($this->originalVersion != $row['version']) {
                Log::warn("[-- tryReload " . $this->getClassName() . " : " . $this->id . " version( {$this->originalVersion} => {$row['version']} ) --]");
            }

            $this->originalVersion = $this->currentVersion = $row['version'];

            // 赋值
            foreach ($this->_keys as $key) {
                $this->_cols[$key] = $row[$key];
            }
        }
    }

    // 更改创建时间，绕过了只读限制，为后门，一般不用
    public function changeCreateTime($time) {
        if (!empty($time))
            $this->createtime = $time;
    }

    // 获取创建日期 2015-09-01
    public function getCreateDay() {
        return substr($this->createtime, 0, 10);
    }

    // 获取创建星期
    public function getCreateW() {
        return XDateTime::get_chinese_weekday($this->createtime);
    }

    // 获取创建日期加星期 2015-09-01 周二
    public function getCreateDayW() {
        return $this->getCreateDay() . " " . $this->getCreateW();
    }

    // 获取创建日期 2015-09-01 13
    public function getCreateDayH() {
        return substr($this->createtime, 0, 13);
    }

    // 获取创建日期 2015-09-01 13:44
    public function getCreateDayHi() {
        return substr($this->createtime, 0, 16);
    }

    // 获取创建日期 13:44
    public function getCreateHi() {
        return substr($this->createtime, 11, 5);
    }

    // 获取创建日期 09-01 13:44
    public function getCreatemdHi() {
        return substr($this->createtime, 5, 11);
    }

    // 获取创建时间加星期 2015-09-01 11:12 周二
    public function getCreatemdHiW() {
        return $this->getCreatemdHi() . " " . $this->getCreateW();
    }

    // 获取创建日期 09-01 13
    public function getCreatemdH() {
        return substr($this->createtime, 5, 8);
    }

    // 获取修改日期
    public function getUpdateDay() {
        return substr($this->updatetime, 0, 10);
    }

    // 获取修改星期
    public function getUpdateW() {
        return XDateTime::get_chinese_weekday($this->updatetime);
    }

    // 获取修改日期加星期 2015-09-01 周二
    public function getUpdateDayW() {
        return $this->getUpdateDay() . " " . $this->getUpdateW();
    }

    // 获取修改日期 2015-09-01 13
    public function getUpdateDayH() {
        return substr($this->updatetime, 0, 13);
    }

    // 获取修改日期 2015-09-01 13:44
    public function getUpdateDayHi() {
        return substr($this->updatetime, 0, 16);
    }

    // 从实体生成数组，在需要时覆写
    public function toArray() {
        return array_merge(
            array(
                $this->getPkeyname() => $this->id,
                'version' => $this->currentVersion,
                'createtime' => $this->createtime,
                'updatetime' => $this->updatetime), $this->_cols);
    }

    public function toSimpleArray() {
        return $this->_cols;
    }

    // reload 实验 end
    // ////////////////////////////////*/

    // 生成insert命令
    public function getInsertCommand() {
        return Dao::getInsertCommand($this);
    }

    // 生成update命令
    public function getUpdateCommand() {
        return Dao::getUpdateCommand($this);
    }

    // 取得删除实体的sql语句，包括删除的修正sql语句
    public function getDeleteCommand() {
        return Dao::getDeleteCommand($this);
    }
}

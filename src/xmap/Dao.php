<?php
namespace Xwork\xmap;
use Xwork\xcommon\DBC;
use Xwork\xcommon\Config;
use Xwork\xcommon\log\Log;

/**
 * Dao
 * @desc		数据库访问对象
 * @remark		依赖类: Entity , Debug , BeanFinder , DBC , Config , DbExecuter , TableNameCreator
 * @copyright 	(c)2012 xwork.
 * @file		Dao.class.php
 * @author		shijianping <shijpcn@qq.com>
 * @date		2012-02-26
 */
class Dao extends DaoBase
{

    // 实体转换,覆盖了基类的方法
    protected function row2Object ($row) {
        $row = array_change_key_case($row, CASE_LOWER);

        $id = $row[$this->pkeyName];
        $version = $row['version'];

        // 必须检查工作单元里是否有了，避免clone体的出现,这个是个核心的思想
        $unitOfWork = BeanFinder::getUnitOfWork();
        $entity = $unitOfWork->getEntity($id, $this->entityClassName, $this->database);
        if ($entity instanceof EntityBase) {
            if ($entity->version != $version) {
                //仍然采用旧对象, 可能会导致更新失败: 宁可失败, 避免错误
                Log::info("[-- row2Object [{$this->entityClassName}][{$id}]->version [{$entity->version} <> {$version}] --]");
            }

            return $entity;
        }

        $createtime = $row["createtime"];
        $updatetime = $row["updatetime"];

        // fix bad data
        foreach ($row as $k => $v) {
            if (is_null($v)) {
                $row[$k] = "";
            }

            // 单引号替换为中文引号
            if (is_string($v)) {
                $v = str_ireplace("\'", '’', $v);
                $v = str_ireplace("'", '’', $v);
            }
        }

        Entity::$needConstructCheckCount ++;
        DBC::$needDBC ++;

        $dbconf = array();
        $dbconf['tableno'] = $this->tableno;
        $dbconf['database'] = $this->database;
        $entityClassName = $this->entityClassName;
        echo $entityClassName, "\n";
        $entity = new $entityClassName($row, $dbconf, $id, $version, $createtime, $updatetime);

        DBC::$needDBC --;
        Entity::$needConstructCheckCount --;

        return $entity;
    }

    // 用bind构造cond
    private static function bind2CondAndBind ($bind = array()) {
        $bindBak = $bind;

        $bind = array();
        $cond = "";

        foreach ($bindBak as $k => $v) {
            $k = str_replace(':', '', $k);
            $k = trim($k);
            $cond .= " AND {$k}=:{$k}";
            $bind[":{$k}"] = $v;
        }

        $cond .= " ";

        return array(
            $cond,
            $bind);
    }

    // /////////////////////////////////////////////////////////////
    // 静态方法 sql 语句生成方法

    // /////////////////////////////////////////////////////////////
    // 共用方法
    // 生成insert命令 , Entity or XEntity
    public static function getInsertCommand(EntityBase $entity) {
        $tableName = self::getTableNameImp($entity->getClassName(), $entity->getTableNo(), $entity->getDatabaseName());
        $row = $entity->toArray();

        $columns = array();
        $bindColumns = array();
        $bindValues = array();
        foreach ($row as $column => $value) {
            // 单引号替换为中文引号
            if (is_string($value)) {
                $value = str_ireplace("\'", '’', $value);
                $value = str_ireplace("'", '’', $value);
            }

            $columns[] = "`{$column}`";
            $bindColumns[] = ":" . $column;
            $bindValues[":" . $column] = $value;
        }

        $columns = implode(",", $columns);
        $bindColumns = implode(",", $bindColumns);

        $sqls[] = array(
            'database' => $entity->getDatabaseName(),
            'sql' => "insert into {$tableName} ({$columns}) values ({$bindColumns})",
            'param' => $bindValues);

        // 修补,如果不能保证肯定能更新数据请不要覆写此函数
        $sqlsFix = $entity->getInsertSqlsFix();

        // 并集
        $sqls = array_merge($sqls, $sqlsFix);

        return $sqls;
    }

    // 生成update命令
    public static function getUpdateCommand (Entity $entity) {
        $tableName = self::getTableNameImp($entity->getClassName(), $entity->getTableNo(), $entity->getDatabaseName());
        $row = $entity->toArray();
        $dirtyKeys = $entity->getDirtyKeys();
        foreach ($dirtyKeys as $column) {
            $value = $row[$column];

            // 单引号替换为中文引号
            if (is_string($value)) {
                $value = str_ireplace("\'", '’', $value);
                $value = str_ireplace("'", '’', $value);
            }

            $updateParty[] = "`{$column}`=:{$column}";
            $bindValues[":" . $column] = $value;
        }
        $updateStr = implode(",", $updateParty);
        // 加了version 条件 by sjp 20091111
        $originalversion = $entity->originalversion;

        $update_need_check_version = Config::getConfig("update_need_check_version", false);
        $cond_fix = "";
        if ($update_need_check_version) {
            $cond_fix = " and version={$originalversion} ";
        }

        $sqls[] = array(
            'database' => $entity->getDatabaseName(),
            'sql' => "update {$tableName} set {$updateStr} where `{$entity->getPkeyname()}`={$entity->id} {$cond_fix} ",
            'param' => $bindValues);
        // $sqls[] = array('sql'=> "update $tableName set $updateStr where
        // id={$entity->id}",'param'=>$bindValues);//TODO 暂时容忍极少出现的并发冲突

        // 修补,如果不能保证肯定能更新数据请不要覆写此函数
        $sqlsFix = $entity->getUpdateSqlsFix();

        // 并集
        $sqls = array_merge($sqls, $sqlsFix);

        return $sqls;
    }

    // 取得删除实体的sql语句，包括删除的修正sql语句
    public static function getDeleteCommand (Entity $entity) {
        $tableName = self::getTableNameImp($entity->getClassName(), $entity->getTableNo(), $entity->getDatabaseName());
        // 加了version 条件 by sjp 20091111
        $originalversion = $entity->originalversion;
        // $sqls[] = array('sql'=> "delete from $tableName where
        // id={$entity->id} and version=$originalversion",'param'=>array());
        $sqls[] = array(
            'database' => $entity->getDatabaseName(),
            'sql' => "delete from {$tableName} where `{$entity->getPkeyname()}`={$entity->id}",
            'param' => array()); // TODO
                                 // 暂时容忍极少出现的并发冲突

        // 修补,如果不能保证肯定能更新数据请不要覆写此函数
        $sqlsFix = $entity->getDeleteSqlsFix();
        $sqls = array_merge($sqlsFix, $sqls);

        return $sqls;
    }

    // /////////////////////////////////////////////////////////////
    // 通用方法-获取实体或实体数组
    // 替代各实体里面的 getById,返回值为实体
    public function getEntityById ($id) :? EntityBase {
        return $this->getById($id);
    }

    // 通用实体查询方法,返回值为实体
    public function getEntityByCond ($cond = "", $bind = array()) {
        return $this->getByCond($cond, $bind);
    }

    // 通用实体列表查询方法,返回值为实体数组
    public function getEntityListByCond ($cond = "", $bind = array()) {
        return $this->getArrayByCond($cond, $bind);
    }

    // 通用实体分页查询方法,返回值为实体数组
    public function getEntityListByCond4Page ($pagesize, $pagenum, $cond = "", $bind = array()) {
        return $this->getArrayByCond4Page($cond, $pagesize, $pagenum, $bind);
    }

    // 通用实体查询方法,返回值为实体, getEntityByCond 的特例,简化版
    public function getEntityByBind ($bind = array()) {
        list ($cond, $bind) = self::bind2CondAndBind($bind);
        return $this->getByCond($cond, $bind);
    }

    // 通用实体列表查询方法,返回值为实体数组, getEntityListByCond 的特例,简化版
    public function getEntityListByBind ($bind = array()) {
        list ($cond, $bind) = self::bind2CondAndBind($bind);
        return $this->getArrayByCond($cond, $bind);
    }

    // 根据ids获取数据
    public function getEntityListByIds ($ids = array()) {
        return $this->getArrayByIds($ids);
    }

    // 直接sql，加载实体，(select * from ....) or (select a.* from ....)
    public function loadEntity ($sql, $bind = array()) {
        return $this->load($sql, $bind);
    }

    // 直接sql，加载实体列表，(select * from ....) or (select a.* from ....)
    public function loadEntityList ($sql, $bind = array()) {
        return $this->loadArray($sql, $bind);
    }

    // 直接sql，加载实体列表，(select * from ....) or (select a.* from ....)
    public function loadEntityList4Page ($sql, $pagesize = 1000, $pagenum = 1, $bind = array()) {
        return $this->loadArray4Page($sql, $pagesize, $pagenum, $bind);
    }
}

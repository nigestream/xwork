<?php

namespace Xwork\xmap\db;

use Xwork\xcommon\log\Log;
use Xwork\xexception\DbException;

/**
 * DbExecuter
 * @desc        数据库执行器
 * @remark        依赖类: Config , Debug , BeanFinder , PDODataSourceConfig , RwMysqlDataSource , PDODataSource , MysqlDataSource
 * @copyright    (c)2012 xwork.
 * @file        DbExecuter.class.php
 * @author        shijianping <shijpcn@qq.com>
 * @date        2012-02-26
 */
class DbExecuter
{

    // 缓存数据库连接
    private $dataSource = null;

    private $reConnectionCnt = 0;

    private $writeConnection = null;

    private $readConnection = null;

    // 设置数据读取模式
    private $fetchMode = \PDO::FETCH_ASSOC;

    // 是否读写分离
    private $rwSplit = true;

    private $rwSplit_CheckPoint = true;

    // 执行的数据库,可以修改,慎重使用 by sjp
    private $database = "";

    public function setDatabase($database = "") {
        $this->database = $database;
    }

    // 当前数据库
    public function getDatabase() {
        return $this->database;
    }

    // 用于记log
    private function getDatabasePre() {
        if (empty($this->database)) {
            return "";
        } else {
            return $this->database . ": ";
        }
    }

    // 由数据源类构造数据库执行器
    public function __construct($dataSource, $database = "") {
        $this->dataSource = $dataSource;
        $this->database = $database;
        $this->reConnection();
    }

    // 重新连接数据库
    public function reConnection() {
        $this->reConnectionCnt++;

        if ($this->reConnectionCnt > 3) {
            die($this->getDatabasePre() . "reConnectionCnt faild!");
        }

        $dataSource = $this->dataSource;
        $this->writeConnection = $dataSource->connect4Write();
        $this->readConnection = $dataSource->connect4Read();

        if (!$this->writeConnection || !$this->readConnection) {
            $this->reConnection();
        }

        $this->reConnectionCnt = 1;
    }

    private function log($echoSql, $costTime, $rowCnt) {
        $databasePre = $this->getDatabasePre();

        $str = $databasePre . $echoSql;

        // xworkdb: sql做特殊处理
        if (trim($databasePre) == 'xworkdb:') {
            if (strpos($echoSql, 'insert') === 0) {
                $pos = strpos($echoSql, '(');
                $str = "[-- " . $databasePre . substr($echoSql, 0, $pos) . " ... --]";
            } elseif (strpos($echoSql, 'update') === 0) {
                $pos = strpos($echoSql, 'set');
                $str = "[-- " . $databasePre . substr($echoSql, 0, $pos) . " ... --]";
            }
        }

        self::logImp($str, $costTime, $rowCnt);
    }

    public static function logImp($echoSql, $costTime, $rowCnt) {
        $costTime = sprintf('%.2f', $costTime);
        // errlog
        $tooManyLines = "";
        if ($rowCnt > 100) {
            $tooManyLines = "(\e[35mtoomanyrows\e[m)";
        }

        $slowLevel = "";
        if ($costTime < 1) {
            // $slowLevel = "0ms";
        } elseif ($costTime >= 1 && $costTime < 10) {
            // $slowLevel = "1ms";
        } elseif ($costTime >= 10 && $costTime < 100) {
            $slowLevel = "10ms";
        } elseif ($costTime >= 100 && $costTime < 1000) {
            $slowLevel = "100ms";
        } elseif ($costTime >= 1000 && $costTime < 10000) {
            $slowLevel = "1s";
        } elseif ($costTime >= 10000 && $costTime < 100000) {
            $slowLevel = "10s";
        } else {
            $slowLevel = "100s";
        }

        if ($slowLevel) {
            $slowLevel = " | \e[35mslow{$slowLevel}\e[m";
        }

        $logstr = "$echoSql; (rowcnt={$rowCnt}) {$tooManyLines} ({$costTime} ms{$slowLevel})";
        Log::sql($logstr);

        Log::addSql($echoSql, $costTime, false);
    }

    // 为执行sql不返回查询内容
    private function execute($sql, $bind = array()) {
        // 修正null值
        if (isset($bind) && !empty($bind) && !is_null($bind) && is_array($bind)) {
            foreach ($bind as $k => $v) {
                if (is_null($v)) {
                    $bind[$k] = '';
                }
            }
        }

        $echoSql = self::buildSql($sql, $bind);

        try {
            $timeStart = microtime(true);

            // 带编译功能
            $stmt = $this->writeConnection->prepare($sql);
            $stmt->execute($bind);
            $result = $stmt->rowCount();
            $costTime = (microtime(true) - $timeStart) * 1000;

            // log
            $this->log($echoSql, $costTime, $result);

            return $result;
        } catch (\Exception $ex) {
            $str = "SqlError : {$echoSql}";
            Log::error($str);
            Log::error($ex->getTraceAsString());

            throw new DbException($str);
        }
    }

    // 执行更新或删除语句
    public function executeNoQuery($sql, $bind = array()) {
        return $this->execute($sql, $bind);
    }

    // 通过sql语句查询数据库，返回所有结果
    public function query($sql, $bind = array()) {
        try {
            return $this->queryImp($sql, $bind);
        } catch (\Exception $e) {
            // 如果异常原因为执行超时，重新连接数据库一次
            if (preg_match("/gone away/i", $e->getMessage()) > 0) {
                $this->reConnection();
                return $this->queryImp($sql, $bind);
            } else {
                throw $e;
            }
        }
    }

    // 通过sql语句查询数据库，返回所有结果
    public function queryImp($sql, $bind = array()) {
        // 修正null值
        if (isset($bind) && !empty($bind) && !is_null($bind) && is_array($bind)) {
            foreach ($bind as $k => $v) {
                if (is_null($v)) {
                    $bind[$k] = '';
                }
            }
        }

        // use rw split, then read from slave db
        if ($this->rwSplit) {
            $connection = $this->readConnection;
        } else {
            $connection = $this->writeConnection;
        }

        $startTime = microtime(true);

        if (isset($bind) && !empty($bind) && !is_null($bind) && is_array($bind)) {
            $stmt = $connection->prepare($sql);
            $stmt->execute($bind);
        } else {
            $stmt = $connection->query($sql);
        }

        $rowSet = $stmt->fetchAll($this->fetchMode);
        $costTime = (microtime(true) - $startTime) * 1000;
        $echoSql = self::buildSql($sql, $bind);

        // log
        $this->log($echoSql, $costTime, count($rowSet));

        return $rowSet;
    }

    // 通过sql语句查询第一行第一列数值
    public function queryValue($sql, $bind = array()) {
        // 修正sql 补 limit 1
        $str1 = stristr($sql, "limit");
        $str2 = stristr($sql, ";");
        if (empty($str1) && empty($str2)) {
            $sql .= " limit 1 ";
        }

        $rs = $this->query($sql, $bind);
        if (empty($rs))
            return null;
        return array_shift($rs[0]);
    }

    // 通过sql语句查询第一列数据
    public function queryValues($sql, $bind = array()) {
        $rs = $this->query($sql, $bind);
        if (empty($rs))
            return array();
        $arr = array();
        foreach ($rs as $row) {
            $arr[] = array_shift($row);
        }
        return $arr;
    }

    // 通过sql语句查询第一行数据
    public function queryOneRow($sql, $bind = array()) {
        $rs = $this->query($sql, $bind);
        if (empty($rs))
            return array();
        return array_shift($rs);
    }

    // 通过sql语句查询数据库，带翻页接口
    public function queryForPage($sql, $pagesize, $pagenum, $bind = array()) {
        $offset = ($pagenum - 1) * $pagesize;
        $sql = DbExecuter::limit($sql, $offset, $pagesize);
        return $this->query($sql, $bind);
    }

    // 往表中插入数据，例
    // BeanFinder::getDbExectuer()->insert('users',array('username'=>'Lily','age'=>'56'));
    public function insert($table, $bind) {
        // col names come from the array keys
        $cols = array_keys($bind);

        // build the statement
        $sql = "INSERT INTO $table " . '(' . implode(', ', $cols) . ') ' . 'VALUES (:' . implode(', :', $cols) . ')';

        // execute the statement and return the number of affected rows
        return $this->execute($sql, $bind);
    }

    // 更新表中数据，where为限制更新范围的sql语句
    public function update($table, $bind, $where) {
        // build "col = :col" pairs for the statement
        $set = array();
        foreach ($bind as $col => $val) {
            $set[] = "$col = :$col";
        }

        // build the statement
        $sql = "UPDATE $table " . 'SET ' . implode(', ', $set) . (($where) ? " WHERE $where" : '');

        // execute the statement and return the number of affected rows
        return $this->execute($sql, $bind);
    }

    // 删除表中数据，where为限制删除范围的sql语句
    public function delete($table, $where) {
        // build the statement
        $sql = "DELETE FROM $table" . (($where) ? " WHERE $where" : '');

        // execute the statement and return the number of affected rows
        return $this->execute($sql);
    }

    // 开始数据库事务
    public function beginTransaction() {
        return $this->writeConnection->beginTransaction();
    }

    // 提交数据库事务
    public function commit() {
        return $this->writeConnection->commit();
    }

    // 回滚数据库事务
    public function rollBack() {
        return $this->writeConnection->rollBack();
    }

    // 释放数据库连接
    public function releaseConnection() {
        unset($this->writeConnection);
        $this->writeConnection = null;

        unset($this->readConnection);
        $this->readConnection = null;
    }

    // 给字符串加反单引号
    public static function quoteIdentifier($ident) {
        $ident = str_replace('`', '\`', $ident);
        return "`{$ident}`";
    }

    // 在sql语句中加入限制范围的语句
    public static function limit($sql, $offset, $count) {
        if ($count > 0) {
            $offset = ($offset > 0) ? $offset : 0;
            $sql .= " LIMIT $offset, $count";
        }
        return $sql;
    }

    // 返回数据库写连接
    public function getWriteConnection() {
        return $this->writeConnection;
    }

    // 返回数据库读连接
    public function getReadConnection() {
        return $this->readConnetion;
    }

    // 是否读写分离,reload 需要
    public function isRwSplit() {
        return $this->rwSplit && $this->writeConnection != $this->readConnection;
    }

    // set rw split flag
    public function needRwSplit() {
        $this->rwSplit = true;
    }

    // unset rw split flag
    public function unNeedRwSplit() {
        $this->rwSplit = false;
    }

    public function saveNeedRwSplit() {
        $this->rwSplit_CheckPoint = $this->rwSplit;
    }

    public function restoreRwSplit() {
        $this->rwSplit = $this->rwSplit_CheckPoint;
    }

    // 通过预编译参数编译完整的sql语句
    public static function buildSql($sql, $bind) {
        if (empty($bind)) {
            return $sql;
        }

        // 逆向排序
        krsort($bind);
        $count = count($bind);
        $bindcount = 0;
        for ($i = 0; $i < $count; ++$i) {
            if (isset($bind[$i]))
                $bindcount++;
        }
        if ($bindcount == $count) {
            foreach ($bind as $value) {
                $sql = preg_replace('/\?/', "'{$value}'", $sql, 1);
            }
        } else {
            foreach ($bind as $key => $value) {
                $sql = str_replace($key, "'{$value}'", $sql);
            }
        }
        return $sql;
    }
}

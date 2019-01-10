<?php
namespace Xwork\xmap\db;
/**
 * MysqlDataSource
 * @desc		mysql数据源
 * @remark		依赖类: PDODataSource
 * @copyright 	(c)2012 xwork.
 * @file		MysqlDataSource.class.php
 * @author		shijianping <shijpcn@qq.com>
 * @date		2012-02-26
 */
class MysqlDataSource extends PDODataSource
{

    public function __construct ($dbhost, $dbname, $username, $password) {
        parent::__construct("mysql:host={$dbhost};dbname={$dbname}", $username, $password);
    }
}
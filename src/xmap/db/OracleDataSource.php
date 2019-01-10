<?php
namespace Xwork\xmap\db;
/**
 * OracleDataSource
 * @desc		oracle数据源
 * @remark		依赖类: PDODataSource
 * @copyright 	(c)2012 xwork.
 * @file		OracleDataSource.class.php
 * @author		shijianping <shijpcn@qq.com>
 * @date		2012-02-26
 */
class OracleDataSource extends PDODataSource
{

    public function __construct ($dbhost, $dbname, $username, $password) {
        parent::__construct("oci:dbname=//{$dbhost}:1521/{$dbname}", $username, $password);
    }
}
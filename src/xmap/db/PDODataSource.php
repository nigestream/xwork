<?php
namespace Xwork\xmap\db;
use Xwork\xcommon\log\Log;
use PDO;

/**
 * PDODataSource
 * @desc		数据源类接口
 * @remark		依赖类: Debug
 * @copyright 	(c)2012 xwork.
 * @file		PDODataSource.class.php
 * @author		shijianping <shijpcn@qq.com>
 * @date		2012-02-26
 */
abstract class PDODataSource
{
    // 数据源名称
    protected $dsn;
    // 数据库用户名
    protected $username;
    // 数据库密码
    protected $password;

    // 数据库连接
    protected $connection = null;

    // 构造函数
    protected function __construct ($dsn, $username, $password) {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
    }

    // 连接数据库,静态方法
    public static function connect ($dsn, $username, $password) {
        // if (isset($_GET['sjp'])) {
        // echo "<br>$dsn,$username,$password<br>";
        // }
        try {
            $connection = new PDO($dsn, $username, $password);
            // for mysql cache, after php5.2 will self-motion support(can delete
            // this code).
            // $connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

            // force names to lower case
            $connection->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

            // always use exceptions.
            $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sql = "set names 'utf8'";
            $connection->exec($sql);

            Log::addNotice("[-- $dsn : $username : $sql; --]");
            Log::sql("[-- $dsn : $username : $sql; --]");
            Log::addSql("$dsn : $username : $sql", 0, false);

        } catch (\Exception $ex) {
            echo "系统忙碌，请重试。";
            $str = "[-- db connect failed! please check db or username or password. --]";

            Log::error($str, $ex->getTraceAsString());

            $connection = null;
        }

        return $connection;
    }

    public function connect4Write () {
        if (is_null($this->connection)) {
            return $this->connection = self::connect($this->dsn, $this->username, $this->password);
        }
        try {
            $this->connection->exec("set names 'utf8mb4'");
        } catch (\Exception $ex) {
            $this->connection = self::connect($this->dsn, $this->username, $this->password);
        }
        return $this->connection;

    }

    // 需要时重载
    public function connect4Read () {
        return $this->connect4Write();
    }

    public function getAllReadConnects () {
        return array(
            $this->connect4Read());
    }

}

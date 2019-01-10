<?php
namespace Xwork\xmap\db;
use Xwork\xcommon\Config;
use Xwork\xcommon\DBC;
use Xwork\xcommon\log\Log;
use Xwork\xcommon\XUtility;

/**
 * DbMgr
 * @desc		DbExecuter生成器,根据需要可以继承
 * @remark		依赖类: 无
 * @copyright	(c)2012 xwork.
 * @file		DbMgr.class.php
 * @author		shijianping <shijpcn@qq.com>
 * @date		2012-02-26
 */
class DbMgr
{

    public function getDbexecuter ($database = "") {
        $timeStart = microtime(true);
        $dataSource = self::getDataSourceByConfig($database);

        if (empty($dataSource)) {
            $dataSource = $this->getDataSourceByHook($database);
        }

        if (empty($dataSource)) {
            DBC::requireTrue(false, "[-- InitDbExecuter.{$database} failed! --]");
        }

        $dbe = new DbExecuter($dataSource, $database);

        $costTime = XUtility::getCostTime($timeStart);
        DbExecuter::logImp("[-- InitDbExecuter.{$database} --]", $costTime, 0);

        return $dbe;
    }

    // 需要继承
    protected function getDataSourceByHook ($database = "") {
        return null;
    }

    // 获取新数据源
    protected static function getDataSourceByConfig ($database = "") {
        $databaseConfig = Config::getConfig("database");
        if (empty($database)) {
            $masterConfig = $databaseConfig["master"];
            $slaveConfigs = $databaseConfig["slaves"];
        } else {
            $masterConfig = $databaseConfig[$database]["master"];
            $slaveConfigs = $databaseConfig[$database]["slaves"];

            if (empty($masterConfig) || empty($slaveConfigs)) {
                DbExecuter::logImp("dbConfig:{$database} is null", 0, 0);
                return null;
            }
        }

        return self::createRwDataSource($masterConfig, $slaveConfigs);
    }

    // 创建一个RwMysqlDataSource
    protected static function createRwDataSource ($masterConfig, $slaveConfigs) {
        $master_db_port = "";
        if (isset($masterConfig['db_port'])) {
            $master_db_port = $masterConfig['db_port'];
        }

        $masterDataSourceConfig = new PDODataSourceConfig("mysql:host={$masterConfig['db_host']};port={$master_db_port};dbname={$masterConfig['db_database']}",
                $masterConfig['db_username'], $masterConfig['db_password']);
        $slaveDataSourceConfigs = array();
        foreach ($slaveConfigs as $key => $config) {
            $db_port = "";
            if (isset($config['db_port'])) {
                $db_port = $config['db_port'];
            }

            $slaveDataSourceConfigs[$key] = new PDODataSourceConfig("mysql:host={$config['db_host']};port={$db_port};dbname={$config['db_database']}",
                    $config['db_username'], $config['db_password'], $config['db_hitratio']);
        }

        return new RwMysqlDataSource($masterDataSourceConfig, $slaveDataSourceConfigs);
    }
}

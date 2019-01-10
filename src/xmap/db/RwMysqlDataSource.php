<?php
namespace Xwork\xmap\db;
/**
 * RwMysqlDataSource
 * @desc		mysql数据源类
 * @remark		依赖类: PDODataSource
 * @copyright 	(c)2012 xwork.
 * @file		RwMysqlDataSource.class.php
 * @author		shijianping <shijpcn@qq.com>
 * @date		2012-02-26
 */
class RwMysqlDataSource extends PDODataSource
{

    private $writeConnectionConfig;

    private $readConnectionConfigs;

    private $readConnection = null;

    private $hitTargets = array();

    public function __construct ($writeConnectionConfig, $readConnectionConfigs) {
        assert(isset($writeConnectionConfig) && ! empty($writeConnectionConfig) && $writeConnectionConfig instanceof PDODataSourceConfig);
        $this->writeConnectionConfig = $writeConnectionConfig;

        parent::__construct($writeConnectionConfig->dsn, $writeConnectionConfig->username, $writeConnectionConfig->password);

        assert(isset($readConnectionConfigs) && ! empty($readConnectionConfigs) && is_array($readConnectionConfigs));
        $this->readConnectionConfigs = $readConnectionConfigs;

        foreach ($this->readConnectionConfigs as $key => $readConnectionConfig) {
            $this->hitTargets = array_merge($this->hitTargets, array_fill(0, $readConnectionConfig->hitRatio, $key));
        }
    }

    public function connect4Read () {
        if (is_null($this->readConnection)) {
            $hitTargetKeys = array_keys($this->hitTargets);
            $randomReadConnectionConfigKey = $this->hitTargets[$hitTargetKeys[mt_rand(0, count($hitTargetKeys) - 1)]];
            $readConnectionConfig = $this->readConnectionConfigs[$randomReadConnectionConfigKey];

            if ($this->writeConnectionConfig->equal($readConnectionConfig)) {
                $this->readConnection = $this->connect4Write();
            } else {
                $this->readConnection = self::connect($readConnectionConfig->dsn, $readConnectionConfig->username, $readConnectionConfig->password);
            }

            return $this->readConnection;
        }

        try {
            $this->readConnection->exec("set names 'utf8mb4'");
        } catch (\Exception $ex) {
            $this->readConnection = self::connect($readConnectionConfig->dsn, $readConnectionConfig->username, $readConnectionConfig->password);
        }
        return $this->readConnection;
    }

    public function getAllReadConnects () {
        $connections = array();
        foreach ($this->readConnectionConfigs as $config) {
            $connections[] = self::connect($config->dsn, $config->username, $config->password);
        }

        return $connections;
    }
}
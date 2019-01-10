<?php
namespace Xwork\xmap\db;
/**
 * PDODataSourceConfig
 * @desc		数据源配置类
 * @remark		依赖类: 无
 * @copyright 	(c)2012 xwork.
 * @file		PDODataSourceConfig.class.php
 * @author		shijianping <shijpcn@qq.com>
 * @date		2012-02-26
 */

class PDODataSourceConfig
{

    public $dsn;

    public $username;

    public $password;

    public $hitRatio; // 命中率

    public function __construct ($dsn, $username, $password, $hitRatio = 1) {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->hitRatio = $hitRatio;
    }

    public function equal ($config) {
        if ($this->dsn == $config->dsn && $this->username == $config->username && $this->password == $config->password)
            return true;
        else
            return false;
    }
}
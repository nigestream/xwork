<?php
/**
 * Created by PhpStorm.
 * User: chendaxian
 * Date: 2018/12/27
 * Time: 下午8:19
 */
namespace Xwork\xcommon\log;
interface ILogger {
    //$logtype 1:normal 2:warn以上
    public function save($logstr, $logtype);
}
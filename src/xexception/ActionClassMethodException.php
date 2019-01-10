<?php
/**
 * Created by PhpStorm.
 * User: chendaxian
 * Date: 2018/12/26
 * Time: 下午7:18
 */
namespace Xwork\xexception;

// 业务异常类
class ActionClassMethodException extends \Exception
{

    public function __construct ($message, $errorCode = 0) {
        parent::__construct($message, $errorCode);
    }
}
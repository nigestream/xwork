<?php
/**
 * Created by PhpStorm.
 * User: chendaxian
 * Date: 2018/12/26
 * Time: 下午7:18
 */
namespace Xwork\xexception;
// 断言异常类
use Xwork\xcommon\log\Log;

class AssertException extends BizException
{

    public function __construct ($message, $errorCode, $logMessage = '') {
        parent::__construct($message, $errorCode);
        if ($logMessage) {
            Log::error($logMessage, $this->getTraceAsString());
        }
    }
}
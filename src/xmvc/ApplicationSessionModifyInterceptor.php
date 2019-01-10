<?php
namespace Xwork\xmvc;
/**
 * ApplicationSessionModifyInterceptor
 * @desc		应用会话修改拦截器
 * @remark		依赖类: Interceptor , XContext , BeanFinder , UnitOfWork
 * @copyright 	(c)2012 xwork.
 * @file		ApplicationSessionModifyInterceptor.class.php
 * @author		shijianping <shijpcn@qq.com>
 * @date		2012-02-26
 */
class ApplicationSessionModifyInterceptor extends Interceptor
{

    public function before () {
        $unitOfWork = BeanFinder::getUnitOfWork();
    }

    public function after () {
        $unitOfWork = BeanFinder::getUnitOfWork();
        if (XContext::getValue("notcommit") != true) {
            $unitOfWork->commit();
            $unitOfWork->bakupReadOnly();
            $unitOfWork->setReadOnly(true);
        }
    }
}

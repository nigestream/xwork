<?php
namespace Xwork\xmvc;
/**
 * ApplicationSessionReadOnlyInterceptor
 * @desc		应用会话只读拦截器
 * @remark		依赖类: Interceptor , BeanFinder , UnitOfWork
 * @copyright 	(c)2012 xwork.
 * @file		ApplicationSessionReadOnlyInterceptor.class.php
 * @author		shijianping <shijpcn@qq.com>
 * @date		2012-02-26
 */
class ApplicationSessionReadOnlyInterceptor extends Interceptor
{

    public function before () {
        $unitOfWork = BeanFinder::getUnitOfWork();
        $unitOfWork->setReadOnly(UnitOfWork::READ_ONLY);
    }

    public function after () {}
}

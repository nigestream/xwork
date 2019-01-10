<?php
namespace Xwork\xmvc;
/**
 * Interceptor
 * @desc		拦截器,面向方面的编程思想
 * @remark		依赖类: 无
 * @copyright (c) 2012 xwork.
 * @file		Interceptor.class.php
 * @author		shijianping <shijpcn@qq.com>
 * @date		2012-02-26
 */
abstract class Interceptor
{

    public abstract function before ();

    public abstract function after ();
}

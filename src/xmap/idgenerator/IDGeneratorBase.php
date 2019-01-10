<?php
namespace Xwork\xmap\idgenerator;
/**
 * @desc		ID生成器
 * @copyright	(c)2012 xwork.
 * @author		shijianping <shijpcn@qq.com>
 * @date		2012-02-26
 */
// 接口
interface IDGeneratorBase
{

    public function getNextID ();

    public function preLoad ($size = 10);
}

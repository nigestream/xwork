<?php
namespace Xwork\xmap;
/**
 * TableNameCreator
 * @desc		表名生成器,根据需要可以继承
 * @remark		依赖类: 无
 * @copyright	(c)2012 xwork.
 * @file		TableNameCreator.class.php
 * @author		shijianping <shijpcn@qq.com>
 * @date		2012-02-26
 */
class TableNameCreator
{

    public function getTableName ($entityClassName, $tableno = 0, $database = '') {

        $pre = '';
        $fix = '';
        if ($tableno > 0) {
            $fix = $tableno;
        }
        $arr = explode("\\", $entityClassName);
        $entityClassName = $arr[count($arr) - 1];

        return $pre . strtolower($entityClassName) . "s$fix";
    }
}
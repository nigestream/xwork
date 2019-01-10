<?php
namespace Xwork\xmvc;
/**
 * XAction
 * @desc		Action基类
 * @remark		依赖类: 无
 * @copyright	(c)2012 xwork.
 * @file		XAction.class.php
 * @author		shijianping <shijpcn@qq.com>
 * @date		2012-02-26
 */
abstract class XAction
{

    const SUCCESS = "success"; // 加载约定成功模板,特例1 setJumpPath 会直接跳转,特例2
                               // setSuccessTemplate 会加载指定模板
    const SYSTEM_ERROR = "error"; // 加载约定失败模板 , setErrorTemplate 会加载指定模板
    const JSON = "json"; // XContext::setValue("outdatas",$outdatas);
    const TEXTJSON = "textjson"; // XContext::setValue("json",$json);
    const JSONP = "jsonp"; // XContext::setValue("outdatas",$outdatas); ,
                           // $_GET['xback'] 为回调函数名
    const PHP = "php"; // XContext::setValue("outdatas",$outdatas);
    const IMG = "img"; // XContext::setValue("data",$data);
    const BLANK = "blank"; // 空模板,就是不需要模板
}
<?php
namespace Xwork\xmvc;
/**
 * XContext
 * @desc		程序上下文类 ,在session中保存request list;保存当前处理流程中的中间对象
 * @remark		依赖类: 无
 * @copyright	(c)2012 xwork.
 * @file		XContext.class.php
 * @author		shijianping <shijpcn@qq.com>
 * @date		2012-02-26
 */
class XContext
{
    // 主要用于存储临时环境变量，供Service使用。
    private static $safeModel = array();
    // 保持执行流程中的中间对象，拦截器和action共享，最后传递给view
    private static $model = array();
    // 跳转路径,中断 nextaction 和 view->render 的执行
    private static $jumpPath = "";
    // 覆写成功页
    private static $successTemplate = "";
    // 覆写失败页
    private static $errorTemplate = "";

    // 挂件相关begin
    private static $isWidget = false;

    private static $widgetModel = array();

    private static $widgetJumpPath = "";

    private static $widgetSuccessTemplate = "";

    private static $widgetErrorTemplate = "";

    public static function startWidget () {
        self::$isWidget = true;
        self::$widgetModel = array();
        self::$widgetJumpPath = "";
        self::$widgetSuccessTemplate = "";
        self::$widgetErrorTemplate = "";
    }

    public static function stopWidget () {
        self::startWidget();
        self::$isWidget = false;
    }

    public static function getWidgetValue ($name) {
        if (isset(self::$widgetModel[$name])) {
            return self::$widgetModel[$name];
        } else {
            return null;
        }
    }
    // 挂件相关end

    public static function getModel () {
        if (self::$isWidget) {
            return self::$widgetModel;
        } else {
            return self::$model;
        }
    }

    public static function clearModel () {
        self::$model = array();
    }

    public static function setValue ($name, $value) {
        // 挂件
        if (self::$isWidget) {
            self::$widgetModel[$name] = $value;
        } else {
            self::$model[$name] = $value;
        }
    }

    public static function getValue ($name) {
        // 挂件
        if (self::$isWidget) {
            return self::getWidgetValue($name);
        }

        if (isset(self::$model[$name])) {
            return self::$model[$name];
        } else {
            return null;
        }
    }

    public static function getValueEx ($key, $default) {
        $value = self::getValue($key);
        return $value !== null ? $value : $default;
    }

    // $jumpPath
    public static function setJumpPath ($jumpPath) {
        // 挂件
        if (self::$isWidget) {
            self::$widgetJumpPath = $jumpPath;
        } else {
            self::$jumpPath = $jumpPath;
        }
    }

    // $jumpPath
    public static function getJumpPath () {
        // 挂件
        if (self::$isWidget) {
            return self::$widgetJumpPath;
        } else {
            return self::$jumpPath;
        }
    }

    // $successTemplate
    public static function setSuccessTemplate ($successTemplate) {
        // 挂件
        if (self::$isWidget) {
            self::$widgetSuccessTemplate = $successTemplate;
        } else {
            self::$successTemplate = $successTemplate;
        }
    }

    // $successTemplate
    public static function getSuccessTemplate () {
        // 挂件
        if (self::$isWidget) {
            return self::$widgetSuccessTemplate;
        } else {
            return self::$successTemplate;
        }
    }

    // $errorTemplate
    public static function setErrorTemplate ($errorTemplate) {
        // 挂件
        if (self::$isWidget) {
            self::$widgetErrorTemplate = $errorTemplate;
        } else {
            self::$errorTemplate = $errorTemplate;
        }
    }

    // $errorTemplate
    public static function getErrorTemplate () {
        // 挂件
        if (self::$isWidget) {
            return self::$widgetErrorTemplate;
        } else {
            return self::$errorTemplate;
        }
    }

    // message
    public static function setMessage ($message) {
        self::setValue("errorMsg", $message);
    }

    public static function getMessage () {
        return self::getValue("errorMsg");
    }

    // safe model
    public static function getSafeModel() {
        return self::$safeModel;
    }

    public static function clearSafeModel () {
        self::$safeModel = [];
    }

    public static function setSafeValue($name, $value) {
        self::$safeModel[$name] = $value;
    }

    public static function getSafeValue($name) {
        if (isset(self::$safeModel[$name])) {
            return self::$safeModel[$name];
        } else {
            return null;
        }
    }
}

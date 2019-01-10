<?php

namespace Xwork\xmvc;

// 递归处理数组, 数组的值统一转化成字符串
use Xwork\xcommon\log\Log;
use Xwork\xexception\ActionClassMethodException;
use Xwork\xexception\AssertException;
use Xwork\xexception\BizException;
use Xwork\xcommon\Config;
use Xwork\xcommon\DBC;
use Xwork\xexception\DbException;
use Xwork\xcommon\XUtility;

/**
 * Class XController
 * @package Xwork\xmvc
 */
class XController
{

    // 缺省配置
    const DEFAULT_INTERCEPTOR = "default_interceptor";

    const DEFAULT_CLASS = "default_class";

    const DEFAULT_METHOD = "default_method";

    // 拦截器key
    const INTERCEPTOR_KEY = "interceptor";

    private $namespace = '';

    private $mapFile = "";

    private $template_root_path = "";

    private $actionMaps = array();

    // 类似于 www
    private $action = "";

    // 类似于 index
    private $method = "";

    // 拦截器
    private $interceptors = array();

    // 错误码
    private $errorCode = -1;

    // 保存处理结果消息errno
    private $errorMsg = "";

    // 保存处理结果消息信息，成功失败均可设置
    public function __construct($namespace, $mapFile, $template_root_path = "") {
        Log::mark_timeStart();
        $this->namespace = $namespace;
        $this->mapFile = $mapFile;
        $this->template_root_path = $template_root_path;
    }

    protected function loadMaps() {
        list($rewrites, $actionMaps) = include_once($this->mapFile);
        $actionMaps = array_change_key_case($actionMaps, CASE_LOWER);
        return [$actionMaps, $rewrites];
    }

    public function process() {
        //拦截非法请求
        $requestMethod = getenv("REQUEST_METHOD");
        if (!in_array(strtoupper($requestMethod), Config::getConfig("allow_methods", ['GET', 'POST', 'HEAD', 'OPTIONS']))) {
            echo "unsupport methods";
            return;
        }
        list ($this->actionMaps, $rewrites) = $this->loadMaps();

        if (Config::getConfig("needUrlRewrite")) {
            XRequest::setRewrites($rewrites);
            XRequest::rewriteRequestByUrl();
        }

        $this->action = XRequest::getValue("xaction", '');
        $this->method = XRequest::getValue("method", '');

        // 缺省action,method
        if (empty($this->action)) {
            $this->action = $this->actionMaps[self::DEFAULT_CLASS];
            $this->method = $this->actionMaps[self::DEFAULT_METHOD];
        }

        try {
            if (empty($this->action)) {
                throw new ActionClassMethodException('action name is null');
            }
            if (empty($this->method)) {
                throw new ActionClassMethodException('method name is null');
            }

            $this->action = strtolower($this->action);
            $this->method = strtolower($this->method);

            XContext::setValue("action", $this->action);
            XContext::setValue("method", $this->method);
            XContext::setValue("action_method", $this->action . '_' . $this->method);

            // 预处理
            $actionClass = $this->getActionClassName();
            $actionMethod = $this->getActionMethod();
            XContext::setValue("action_class", $actionClass);
            XContext::setValue("action_method", $actionMethod);

            // 支持actionclass 大小写不严格
            global $lowerclasspath;
            if (!empty($lowerclasspath)) {
                $realname = null;
                if (isset($lowerclasspath[strtolower($actionClass)])) {
                    $realname = $lowerclasspath[strtolower($actionClass)];
                }
                if (empty($realname)) {
                    throw new ActionClassMethodException("[{$actionClass}::{$actionMethod}] actionClass not found!");
                }
                $actionClass = $realname;
            }

            $this->doProcess($actionClass, $actionMethod);

            XContext::setValue("AllCostTime", Log::getCostTimeFromStart());
        } catch (ActionClassMethodException $ex) {
            $this->outputError($ex, false);
        } catch (AssertException $ex) {
            $this->outputError($ex);
        } catch (\Exception $ex) {
            Log::error($ex->getTraceAsString());
            $this->outputError($ex);
        }

        if (XContext::getValue("fastcgi_finish_request")) {
            // 在这里加日志输出,是针对前面有fastcgi_finish_request的情况
            Log::warn("[-- end for fastcgi_finish_request [1] --]");
            Log::flushXworklog();
        }
    }

    private function doProcess($actionClass, $actionMethod) {
        $str = "[-- {$actionClass}::{$actionMethod} --]";

        Log::addNotice($str);
        Log::sys($str);

        // 执行拦截器们的beforeMethod
        $this->doInteceptorBeforeMethod();

        // 构造actionObject
        $actionClass = "{$this->namespace}\\$actionClass";
        $actionObject = new $actionClass();

        $method_exists = method_exists($actionObject, $actionMethod);
        if (!$method_exists) {
            throw new ActionClassMethodException("[{$actionClass}::{$actionMethod}] actionClass not found!");
        }

        $result = "";
        try {
            // 执行actionMethod
            $result = $actionObject->$actionMethod();
            // action执行完毕的hook函数
            if (method_exists($actionObject, '_hookActionFinish')) {
                $actionObject->_hookActionFinish();
            }

            $this->errorMsg = XContext::getMessage();
        } catch (AssertException $assertEx) {
            XContext::setValue("exception", $assertEx);
            //断言类异常不在这里记日志，应该在DBC类里
//            Log::errlogEx($assertEx, "assertEx");

            $this->errorCode = $assertEx->getCode();
            $this->errorMsg = $assertEx->getMessage();
            XContext::setValue("notcommit", true);
            $result = XAction::SYSTEM_ERROR;
        } catch (BizException $bizEx) {
            XContext::setValue("exception", $bizEx);
            Log::error($bizEx->getTraceAsString());

            $this->errorMsg = $bizEx->getMessage();
            XContext::setValue("notcommit", true);
            $result = XAction::SYSTEM_ERROR;
        } catch (DbException $dbEx) {
            XContext::setValue("exception", $dbEx);
            Log::error($dbEx->getTraceAsString());

            // 主要针对的是并发冲突
            $this->errorMsg = "数据库操作失败，请重试";
            XContext::setValue("notcommit", true);
            $result = XAction::SYSTEM_ERROR;
        } catch (\Exception $ex) {
            XContext::setValue("exception", $ex);
            Log::error($ex->getTraceAsString());

            $this->errorMsg = "系统错误,请联系技术人员";
            if (Config::getConfig('innerSystem')) {
                $this->errorMsg .= ("!内部系统错误提示：<br/>");
                $this->errorMsg .= $ex->getMessage();
                $this->errorMsg .= "<br/>";
                $this->errorMsg .= $ex->getTraceAsString();
            }

            XContext::setValue("notcommit", true);
            $result = XAction::SYSTEM_ERROR;
        }

        // 记录耗时
        Log::logCostTimeStr('MethodEnd: ');

        // 后拦截器,仅catch 数据库异常，尽量输出模板
        try {
            // 执行拦截器们的afterMethod
            $this->doInteceptorAfterMethod();
        } catch (\Exception $dbEx) {
            XContext::setValue("exception", $dbEx);
            Log::error($dbEx->getTraceAsString());

            $this->errorMsg = $dbEx->getMessage();

            if (strpos($this->errorMsg, "qlError") > 0) {
                $this->errorMsg = "并发冲突,请稍候重试";
            }

            XContext::setValue("notcommit", true);
            $result = XAction::SYSTEM_ERROR;

            // 不予跳转
            XContext::setJumpPath("");
        }

        // 记录耗时
        Log::logCostTimeStr('InteceptorAfterMethodEnd: ');

        if (XContext::getValue("fastcgi_finish_request")) {
            // 在这里加日志输出,是针对前面有fastcgi_finish_request的情况
            Log::warn("[-- end for fastcgi_finish_request [2] --]");
            Log::flushXworklog();
        }

        // 允许中断
        if (XContext::getJumpPath() != null && XContext::getJumpPath() != "") {
            Log::sys("JumpPath to : " . XContext::getJumpPath());
            header("Location: " . XContext::getJumpPath());
            // nginx反向代理有个bug ，所以不得不输出点东西
            echo date("Y-m-d h:i:s JumpPath to : " . XContext::getJumpPath());
            exit();
        }

        if (XAction::SUCCESS == $result || empty($result)) {
            $template = $this->geSuccessTemplate();
        } elseif (XAction::SYSTEM_ERROR == $result) {
            $template = $this->getErrorTemplate();
        } elseif (XAction::PHP == $result) {
            echo serialize(XContext::getValue("outdatas"));
            exit();
        } elseif (XAction::IMG == $result) {
            header('Content-type: image/jpeg');
            echo XContext::getValue("data");
            exit();
        } elseif (XAction::JSON == $result) {
            // 以下函数,设置本请求的失效时间为0
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
            header("Cache-Control: no-cache, must-revalidate");
            header("Pragma: no-cache");
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(XContext::getValue("outdatas"), JSON_UNESCAPED_UNICODE);
            exit();
        } elseif (XAction::TEXTJSON == $result) {
            if (isset($_GET["debug"])) {
                echo '<pre>';
                print_r(XContext::getValue("json"));
                exit();
            }

            // 以下函数,设置本请求的失效时间为0
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
            header("Cache-Control: no-cache, must-revalidate");
            header("Pragma: no-cache");
            header('Content-Type: text/json; charset=utf-8');

            $jsondata = XContext::getValue("json");
            $jsondata = XUtility::jsonArrayFix($jsondata);
            if (isset($jsondata['data']) && is_array($jsondata['data']) && empty($jsondata['data'])) {
                $jsondata['data'] = (object)array();
            }
            $str = json_encode($jsondata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            echo str_replace('"[dict]"', '{}', $str);
            exit();
        } elseif (XAction::JSONP == $result) {
            // 以下函数,设置本请求的失效时间为0
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
            header("Cache-Control: no-cache, must-revalidate");
            header("Pragma: no-cache");
            header('Content-Type: text/html; charset=utf-8');
            echo $_GET['xback'] . '(' . json_encode(XContext::getValue("outdatas"), JSON_UNESCAPED_UNICODE) . ')';
            exit();
        } elseif (XAction::BLANK == $result) {
            // 不需要模板
            return;
        } else {
            $template = $this->getOtherTemplate($result);
        }

        // 创建视图
        $view = new XView(XContext::getModel(), $template);
        $view->setValue("errorMsg", $this->errorMsg);

        // 跳转到错误页
        if (XAction::SYSTEM_ERROR == $result) {
            if ($this->isAjax()) {
                $ret['errno'] = $this->errorCode;
                $ret['errmsg'] = $this->errorMsg;
                $ret['data'] = array();
            } else {
                // todo 输出错误模板
                $ret['errno'] = $this->errorCode;
                $ret['errmsg'] = $this->errorMsg;
                $ret['data'] = array();
            }

            header('Content-Type: application/javascript; charset=utf-8');
            echo json_encode($ret, JSON_UNESCAPED_UNICODE);
            exit();
        }

        // 渲染模板
        $view->render();
        // $view->renderSmarty();
    }

    // 完全依赖于约定
    private function getActionClassName() {
        return ucfirst($this->action) . "Action";
    }

    // 完全依赖于约定
    private function getActionMethod() {
        return "do" . ucfirst($this->method);
    }

    // 成功模板
    private function geSuccessTemplate() {
        // 允许中断
        $successTemplate = XContext::getSuccessTemplate();

        // 约定方式
        if (empty($successTemplate)) {
            $successTemplate = $this->action . "/" . $this->method . ".tpl.php"; // 方法名称
        }

        return $this->template_root_path . strtolower($successTemplate);
    }

    // 失败模板
    private function getErrorTemplate() {
        // 允许中断
        $errorTemplate = XContext::getErrorTemplate();

        // 约定方式
        if (empty($errorTemplate)) {
            $errorTemplate = $this->action . "/" . $this->method . "." . XAction::SYSTEM_ERROR . ".tpl.php"; // 方法名称
        }

        $filename = $this->template_root_path . strtolower($errorTemplate);

        if (!file_exists($filename)) {
            $filename = $this->template_root_path . "error.tpl.php";
        }

        return $filename;
    }

    // 其他结果模板
    private function getOtherTemplate($result) {
        DBC::requireNotEmpty($result, "not action return value");
        $resultTemplate = $this->action . "/" . $this->method . ".{$result}.tpl.php";
        return $this->template_root_path . strtolower($resultTemplate);
    }

    // 拦截器 TODO by sjp : 正考虑消灭配置文件，直接返回通用拦截器 ApplicationSessionModifyInterceptor
    private function loadInteceptor() {
        return [];
        $arrays = array();

        if (isset($this->actionMaps) && //
            isset($this->actionMaps[$this->action]) && //
            isset($this->actionMaps[$this->action][$this->method]) && //
            isset($this->actionMaps[$this->action][$this->method][self::INTERCEPTOR_KEY])) {

            // 特定配置
            $interceptors = $this->actionMaps[$this->action][$this->method][self::INTERCEPTOR_KEY];
        } else {
            // 通用配置
            $interceptors = $this->actionMaps[self::DEFAULT_INTERCEPTOR];
        }

        foreach ($interceptors as $interceptorClassName) {
            $arrays[] = new $interceptorClassName();
        }
        return $arrays;
    }

    // 拦截器before
    private function doInteceptorBeforeMethod() {
        foreach ($this->loadInteceptor() as $inter) {
            $inter->before();
            array_push($this->interceptors, $inter);
        }
    }

    // 拦截器after
    private function doInteceptorAfterMethod() {
        // 由于是pop，所以各个拦截器对象，执行完以后都会被销毁
        while ($inter = array_pop($this->interceptors)) {
            $inter->after();
        }
    }

    private function isAjax() {
        return isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest";
    }

    protected function outputError(\Exception $ex, $flushLog = true) {
        $data = array(
            'errno' => $ex->getCode() ? $ex->getCode() . '' : '-1',
            'errmsg' => $ex->getMessage(),
            'data' => (object)[]);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        if (!$flushLog) {
            Config::setConfig("mustXworklog", false);
        }
        exit();
    }
}

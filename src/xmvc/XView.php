<?php
namespace Xwork\xmvc;
use Xwork\xcommon\Debug;

/**
 * XView
 * @desc 		视图类
 * @remark		依赖类: 无
 * @copyright	(c)2012 xwork.
 * @file		XView.class.php
 * @author		shijianping <shijpcn@qq.com>
 * @date		2012-02-26
 */
class XView
{

    private $template;

    private $datas = array();

    public function __construct ($datas, $template) {
        $this->template = $template;
        $this->datas = $datas;
    }

    public function render()
    {
        foreach($this->datas as $key=>$value){
            $$key = $value;
        }

        include_once($this->template);
    }

    public function setValue ($key, $value) {
        assert('' != $key);
        $this->datas[$key] = $value;
    }

    public function getValue ($key) {
        return (isset($this->datas[$key])) ? $this->datas[$key] : '';
    }

    public function getValues () {
        return $this->datas;
    }

    public function setValues ($values) {
        $this->datas = $values;
    }

    // public function renderSmarty()
    // {
    // print $this->getRenderOutput();
    // }
    //
    // private function getRenderOutput()
    // {
    // $template = DefaultViewSetting::getTemplate();
    // DefaultViewSetting::setTemplateSetting($template);
    // $template->assign($this->datas);
    // return $template->fetch($this->template);
    // }
}

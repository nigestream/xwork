<?php
/**
 * Created by PhpStorm.
 * User: chendaxian
 * Date: 2018/12/26
 * Time: 上午10:55
 */
namespace Xwork\xcommon;
interface INoticer {
    public function send($unitofworkId, $brief, $content);
}
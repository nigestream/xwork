<?php
/**
 * Created by PhpStorm.
 * User: chendaxian
 * Date: 2018/12/25
 * Time: 下午8:43
 */
namespace Xwork\xcommon;
interface ICache {
    public function get($k);
    public function set($k, $v, $expire);
    public function del($k);
}
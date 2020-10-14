<?php
/**
 * Created by PhpStorm.
 * User: jayding
 * Date: 2020/9/2
 * Time: 17:35
 */

namespace App\Contracts;


interface SessionContract
{
    // 存储用户信息
    public function setSession($key, $value);

}
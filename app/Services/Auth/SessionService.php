<?php

/**
 * Created by PhpStorm.
 * User: jayding
 * Date: 2020/9/4
 * Time: 08:29
 */

namespace App\Services\Auth;

use App\Contracts\SessionContract;
use App\Models\User\LoginLog;

class SessionService implements SessionContract
{
    /**
     * 存储用户信息
     */
    public function setSession($key, $value)
    {
        // TODO: Implement setSession() method.
//        $log = LoginLog::where('user_id', $value['id']);
        $data = [
            'user_id'    => $value['id'],
            'user_name'  => $value['name'],
            'token'      => $value['access_token'],
            'ip_address' => real_ip(),
            'extension'  => $value['extension'] ?? '',     //  json字符串
        ];
        LoginLog::create($data);
    }

}
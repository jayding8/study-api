<?php

/**
 * aes 解密
 */
if (!function_exists('aes_decrypt')) {
    function aes_decrypt($data)
    {
        $key       = config('auth.aes_key');
        $iv        = config('auth.aes_iv');
        $decrypted = openssl_decrypt(base64_decode(urldecode($data)), 'aes-128-cbc', $key, 1, $iv);

        return $decrypted;
    }
}

/*
* aes 加密
*/
if (!function_exists('aes_encrypt')) {
    function aes_encrypt($data)
    {
        $key       = config('auth.aes_key');
        $iv        = config('auth.aes_iv');
        $decrypted = urlencode(base64_encode(openssl_encrypt($data, 'aes-128-cbc', $key, 1, $iv)));

        return $decrypted;
    }
}

/**
 * 获取真实ip
 */
if (!function_exists('real_ip')) {
    function real_ip()
    {
        $real_ip = '127.0.0.1';
        if (isset($_SERVER['HTTP_REMOTEIP'])) {
            //兼容阿里云获取ip
            $real_ip = $_SERVER['HTTP_REMOTEIP'];
        } else if (request()->server->get('HTTP_X_REAL_IP')) {
            $real_ip = request()->server->get('HTTP_X_REAL_IP');
        } elseif (request()->server->get('HTTP_CLIENT_IP')) {
            $real_ip = request()->server->get('HTTP_CLIENT_IP');
        } elseif (request()->server->get('HTTP_X_FORWARDED_FOR') && preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', request()->server->get('HTTP_X_FORWARDED_FOR'), $matches)) {
            foreach ($matches[0] AS $real_ip) {
                if (!preg_match("#^(10|172\.16|192\.168)\.#", $real_ip)) {
                    break;
                }
            }
        } else {
            $real_ip = request()->server->get('REMOTE_ADDR');
        }

        return $real_ip;
    }
}

/**
 * 验证手机号
 */
if (!function_exists('verify_mobile')) {
    function verify_mobile($mobile)
    {
        $preg = '/^1[0-9]{10}$/';
        $res  = preg_match($preg, $mobile) ? true : false;

        return $res;
    }
}

/**
 * 转换日期格式(date to date)
 */
if (!function_exists('change_date_format')) {
    function change_date_format($date, $format = "Y-m-d", $is_date = true)
    {
        return date($format, $is_date ? strtotime($date) : $date);
    }
}
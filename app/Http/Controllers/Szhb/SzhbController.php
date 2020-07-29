<?php

namespace App\Http\Controllers\Szhb;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class SzhbController extends Controller
{
    public function lists()
    {
        $conn_id = @ftp_connect('10.0.1.202', 21) or die('FTP连接失败');
        @ftp_login($conn_id, 'caoyue', 'yue123456') or die('FTP登录失败');
//        @ftp_pasv($conn_id, 1); // 打开被动模拟

        $list = ftp_nlist($conn_id,'');
        @ftp_close($conn_id);
        return Response::success($list);
    }


}








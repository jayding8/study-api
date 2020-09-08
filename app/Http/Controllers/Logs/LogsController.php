<?php

namespace App\Http\Controllers\Logs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use App\Models\Logs\Logs;

class LogsController extends Controller
{
    public function create(Request $request)
    {
        $rule    = [
            'op_id'     => 'required',
            'op_name'   => 'required|max:255',
            'type'      => 'required',
            'type_name' => 'required|max:255',
        ];
        $message = [
            'op_id.required'     => 'Require op_id Param',
            'op_name.required'   => 'Require op_name Param',
            'op_name.max'        => 'op_name Is Too Long',
            'type.required'      => 'Require type Param',
            'type_name.required' => 'Require type_name Param',
            'type_name.max'      => 'type_name Is Too Long',
        ];
        //验证
        $validator = Validator::make($request->all(), $rule, $message);
        if ($validator->fails()) {
            return response()->error(10000, $validator->errors()->first());
        }
//        dd(auth()->user());
        $insert = [
            'user_id'   => auth()->user()->id,
            'user_name' => auth()->user()->name,
        ];
        $insert = array_merge($insert, $request->all());
        $log = Logs::create($insert);

        return Response::success($log);
    }

    public function list()
    {
        $param = [
            ''
        ];
    }
}

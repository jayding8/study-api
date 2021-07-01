<?php

namespace App\Http\Controllers\Logs;

use App\Http\Controllers\Controller;
use App\Imports\LogsImport;
use App\Models\Logs\Logs;
use App\Models\User\UserWarning;
use App\Services\Third\ExcelService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class LogsController extends Controller
{
    const OP_ID = 2;

    private $op_types, $excelService, $excel_types;

    public function __construct(ExcelService $excelService)
    {
        $this->excelService = $excelService;
        $this->excel_types  = config('kzz.excel');
        $this->op_types     = config('kzz.op');
    }

    public function create(Request $request)
    {
        $rule    = [
            'op_id'     => 'required_without:op_key',
            'op_key'    => 'required_without:op_id',
            'type'      => 'required|numeric',
            'type_name' => 'required|max:5',
        ];
        $message = [
            'op_id.required_without'  => 'Require op_id or op_key param',
            'op_key.required_without' => 'Require op_id or op_key param',
            'type.required'           => 'Require type Param',
            'type.numeric'            => 'Error type type',
            'type_name.required'      => 'Require type_name Param',
            'type_name.max'           => 'type_name Is Too Long',
        ];
        //验证
        $validator = Validator::make($request->all(), $rule, $message);
        if ($validator->fails()) {
            return response()->error(1000, $validator->errors()->first());
        }
//        dd(auth()->user());
        $insert = [
            'user_id'   => auth()->user()->id,
            'user_name' => auth()->user()->name,
        ];
        if ($request->has('op_key')) {
            $op_key = $request->get('op_key');
            $op     = Arr::where($this->op_types, function ($val) use ($op_key) {
                return $val['key'] == $op_key && $val;
            });
            $insert = array_merge($insert, array_values($op)[0]);
        }
        $insert = array_merge($insert, $request->all());
        $log    = Logs::updateOrCreate(Arr::only($insert, ['user_id', 'op_id', 'type']), $insert);
        if (isset($op_key) && $op_key == 'user_warning') {
            $insert['or_id'] = $log->id;
            UserWarning::updateOrCreate(Arr::only($insert, ['user_id', 'type']), Arr::only($insert, ['or_id', 'user_id', 'user_name', 'type', 'type_name', 'up', 'down', 'percent']));
        }

        return Response::success($log);
    }

    public function delete(Request $request)
    {
        $rule    = [
            'op_id' => 'required',
            'types' => 'required',
        ];
        $message = [
            'op_id.required' => 'Require op_id Param',
            'type.required'  => 'Require type Param',
        ];
        //验证
        $validator = Validator::make($request->all(), $rule, $message);
        if ($validator->fails()) {
            return response()->error(1000, $validator->errors()->first());
        }
        $op_id       = $request->get('op_id');
        $delete_data = [
            'op_id' => $op_id,
            'types' => $request->get('types'),
        ];
        Logs::condition($delete_data)->self()->delete();
        if ($op_id == '4') {
            UserWarning::condition(['types' => $request->get('types')])->self()->delete();
        }
        return response()->success($request->get('type_names') ?? 'success');
    }

    public function logs()
    {
        $ops    = Arr::pluck($this->op_types, 'key', 'op_id');
        $logs   = Logs::self()->with('warning')->get()->toArray();
        $return = Arr::pluck($this->op_types, '', 'key');
        foreach ($logs as $log) {
            $log['created_at']             = change_date_format($log['created_at'], 'Y-m-d', 1);
            $return[$ops[$log['op_id']]][] = $log;
        }
        return response()->success($return);
    }

    public function importExcele()
    {
        if (!$file = request()->file('file')) {
            return response()->error(1000, 'NO FILE');
        }

        $extension = $file->getClientOriginalExtension();
        if (!in_array($extension, $this->excel_types)) {
            return response()->error(1001, 'ERROR TYPE: ' . $extension);
        }

        $op_id   = request('op_id') ?? self::OP_ID;
        $op_name = Arr::pluck($this->op_types, 'op_name', 'op_id')[$op_id];
        $params  = [
            'op_id'   => $op_id,
            'op_name' => $op_name,
        ];
        $ret     = $this->excelService->importExcele(new LogsImport($params), $file);
        return response()->success($ret ? 'success' : 'error');
    }
}

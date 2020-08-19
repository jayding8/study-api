<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User\User;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => ['required', 'string', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        if ($validator->fails()) {
            return response()->error(1000, $validator->errors()->first());
        }
//        $user_info = Arr::only($request->all(), ['name', 'password']);
        $user_info = [
            'name'     => $request->get('name'),
            'password' => Hash::make($request->get('password')),
        ];
        $user      = User::create($user_info);
        return response()->success($user);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->error(1000, $validator->errors()->first());
        }
        if (Auth::attempt(['name' => $request->get('name'), 'password' => $request->get('password')])) {
            return response()->success(auth()->user()->toArray());
        }
        return response()->error(1000,'Login Fail');
    }
}

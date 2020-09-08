<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User\User;
use Illuminate\Support\Str;
use App\Contracts\SessionContract;

class UserController extends Controller
{
    public $session;

    public function __construct(SessionContract $session)
    {
        $this->session = $session;
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => ['required', 'string', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone'    => ['required'],
        ]);
        if ($validator->fails()) {
            return response()->error(1000, $validator->errors()->first());
        }
//        $user_info = Arr::only($request->all(), ['name', 'password']);
        $user_info = [
            'name'     => $request->get('name'),
            'password' => Hash::make($request->get('password')),
            'phone'    => $request->get('phone'),
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
            $user_info = auth()->user()->toArray();
            $token     = md5(Str::random(10) . auth()->user()->id);
            $key       = 'user-session:' . $token;

            $user_info['access_token'] = $token;
            $this->session->setSession($key, $user_info);

            return response()->success($user_info);
        }
        return response()->error(1000, 'Login Fail');
    }
}

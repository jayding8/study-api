<?php

namespace App\Http\Middleware;

use App\Models\User\LoginLog;
use App\Models\User\User;
use Closure;
use Illuminate\Support\Facades\Auth;

class Authenticate
{
    public function __construct()
    {

    }

    public function handle($request, Closure $next)
    {
        if ($request['access_token']) {
            $login = LoginLog::where('token', $request['access_token'])->first();
            // 还要判断token是否过期,后期再加
            $user_info = User::find($login->user_id);
            Auth::login($user_info);
        }
        return $next($request);
    }

}

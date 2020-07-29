<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User\User;

class UserController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->error(1000, $validator->errors()->first());
        }

        $username = trim($request->get('name'));
        $password = trim($request->get('password'));
        if (!$password = aes_decrypt($password)) {
            return response()->error(1001, 'Login Failed');
        }
        // 原密码
        $origin_password = $password;

        $remember   = $request->get('remember');
        $credential = ['name' => $username];

        if (!$user = User::where('username', $username)->orWhere('phone', $username)->first()) {
            return response()->error(1001, 'Login Failed');
        }

        if ($user->locked == self::LOCKED) {
            return response()->error(1033, 'User Locked');
        }

        if ($user->expire_time && Carbon::now()->gte(Carbon::parse($user->expire_time)->addDay())) {
            return response()->error(1037, trans('auth::errors.User Expired'));
        }
        // 兼容老 M2O 加盐加密方式
        $login = false;
        if ($user->salt) {
            $password = md5(md5($password) . $user->salt);
            if ($password == $user->password) {
                $login = true;
                Auth::loginUsingId($user->id, $remember);
            }
        }
        $credential['password'] = $password;

        if (Auth::attempt($credential, $remember) || $login) {
            if (auth()->user()->reset_pwd == self::RESET_PWD) {
                return response()->success(['id' => auth()->user()->id, 'reset_pwd' => 1]);
            }
            // 验证密码是否为弱密码 第一次提示
            if (!password_check($origin_password)) {
                $user->update(['reset_pwd' => 1]);
                return response()->error(1033, 'Weak Password Please reset');
            }

            $ip = real_ip();

            auth()->user()->update([
                'last_login_time'   => time(),
                'last_login_ip'     => $ip,
                'login_failed_time' => 0,
                'login_time'        => auth()->user()->login_time + 1
            ]);
            // 初始化用户权限
            $user_prms      = $this->initPrms();
            $user           = auth()->user()->toArray();
            $user['avatar'] = get_image_by_key($user['avatar'], $user['site_id']);
            $token_key      = 'm2o-app:user-token:' . auth()->user()->id;
            $old_token      = Redis::connection('auth')->get($token_key);
            // 没有 token 或者刷新 token 时重新生成 token
            $token = md5(str_random(10) . auth()->user()->id);
            Redis::connection('auth')->setex($token_key, config('session.lifetime') * 60, $token);

            $user['user_prms']    = $user_prms;
            $user['access_token'] = $token;
            // 是否是融合号
            $user['IS_FUSION'] = intval(config('app.is_fusion'));
            // 设置用户信息
            $redis_key = 'm2o-app:user-session:' . $token;
            Redis::connection('auth')->setex($redis_key, config('session.lifetime') * 60, json_encode($user));
            if ($old_token) {
                $redis_key = 'm2o-app:user-session:' . $old_token;
                Redis::connection('auth')->setex($redis_key, config('session.lifetime') * 60, json_encode($user));
            }
            // 日志记录
            event('log', [[
                'module'           => $this->module,
                'submodule'        => $this->submodule,
                'operation'        => 'login',
                'title'            => $user['username'] . '：' . $ip . ' ' . ip_address($ip),
                'origin_id'        => $user['id'],
                'create_user_id'   => auth()->user()->id,
                'create_user_name' => auth()->user()->username,
                'ip'               => real_ip(),
            ]]);
            //虚拟主播初始化
            event(new VirtualAnchorInitEvent());
            // TODO 登录手机号验证之前信息已暴露，不验证也可以访问接口数据，待优化
            // 判断是否需要短信验证
            if (config('system.settings.sms')) {
                $verify_key  = 'm2o-backend:sms-expire:' . $user['id'];
                $is_exist    = Redis::connection('auth')->exists($verify_key);
                $user['sms'] = $is_exist ? false : true;
            } else {
                $user['sms'] = false;
            }

            return response()->success($user);
        } else {
            if ($user->login_failed_time < $this->login_failed_time) {
                $user->increment('login_failed_time');
            }
            // 日志记录
            event('log', [[
                'module'           => $this->module,
                'submodule'        => $this->submodule,
                'operation'        => 'login',
                'title'            => $user['username'] . ' 登陆失败，尝试次数：' . $user->login_failed_time,
                'origin_id'        => $user['id'],
                'create_user_id'   => $user->id,
                'create_user_name' => $user->username,
                'ip'               => real_ip(),
            ]]);
            if ($user->login_failed_time == 3) {
                return response()->error(1001, 'Login Failed three times', 200, $this->login_failed_time);
            }
            if ($user->login_failed_time == $this->login_failed_time) {
                $user->update(['locked' => 1]);
                // 日志记录
                event('log', [[
                    'module'           => $this->module,
                    'submodule'        => $this->submodule,
                    'operation'        => 'lock',
                    'title'            => $user['username'],
                    'origin_id'        => $user['id'],
                    'create_user_id'   => $user->id,
                    'create_user_name' => $user->username,
                    'ip'               => real_ip(),
                ]]);

                return response()->error(1001, 'Password Error User Locked');
            }

            return response()->error(1001, 'Login Failed');
        }
    }
}

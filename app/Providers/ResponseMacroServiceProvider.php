<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;

class ResponseMacroServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Response::macro('success', function ($data = null, $message = '') {
            if ($data === null) {
                $data = 'success';
            }
            return Response::json(['error_code' => 0, 'message' => $message, 'data' => $data, 'datetime' => Carbon::now()->toDateTimeString()]);
        });

        Response::macro('error', function ($error_code, $error_message = null, $status = 200, $sprintf = null, $data = []) {
            $no_code       = $error_message ? false : true;
            $error_message = $error_message ?: $error_code;
            $error_message = trans('errors.' . $error_message);
            if (mb_strpos($error_message, 'errors.') === 0) {
                $error_message = ltrim($error_message, 'errors.');
            }
            if ($sprintf) {
                $error_message = sprintf($error_message, $sprintf);
            }
            $error_code = $no_code ? 1001 : $error_code;

            return Response::json(['error_code' => $error_code, 'error_message' => $error_message, 'data' => $data], $status);
        });

    }
}

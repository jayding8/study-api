<?php

namespace App\Http\Middleware;

use Closure;

class Authenticate
{
    public function __construct()
    {

    }

    public function handle($request, Closure $next)
    {


        return $next($request);
    }

}

<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

class LoginLog extends Model
{
    protected $table = 'login_log';

    protected $fillable = [
        'user_id',
        'user_name',
        'token',
        'ip_address',
        'extension',
    ];
}

<?php

namespace App\Models\User;

use Illuminate\Auth\Authenticatable as Auth;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model implements Authenticatable
{
    use Auth;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'remember_token',
        'phone',
    ];

    protected $hidden = ['password', 'remember_token'];
}

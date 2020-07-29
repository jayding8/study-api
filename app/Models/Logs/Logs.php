<?php

namespace App\Models\Logs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Logs extends Model
{
    use SoftDeletes;

    protected $table = 'operating_record';

    protected $dates = ['delete_at'];

    protected $fillable = [
        'user_id',
        'user_name',
        'op_id',
        'op_name',
        'type',
        'type_name',
    ];

    protected $hidden = ['delete_at'];
}

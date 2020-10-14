<?php

namespace App\Models\Logs;

use App\Models\User\User;
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

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function scopeCondition($query, $params)
    {
        if (isset($params['op_id'])) {
            $query->where('op_id', $params['op_id']);
        }
        return $query;
    }

    public function scopeSelf($query)
    {
        return $query->where('user_id', auth()->user()->id);
    }
}

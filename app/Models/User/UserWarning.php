<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

class UserWarning extends Model
{
    protected $table = 'user_warning';

    protected $fillable = [
        'or_id',
        'user_id',
        'user_name',
        'type',
        'type_name',
        'up',
        'down',
        'percent',
    ];

    public function scopeCondition($query, $params)
    {
        if (isset($params['types'])) {
            if (is_array($params['types'])) {
                $query->whereIn('type', $params['types']);
            } else {
                $query->where('type', $params['types']);
            }
        }

        return $query;
    }

    public function scopeSelf($query)
    {
        return $query->where('user_id', auth()->user()->id);
    }
}

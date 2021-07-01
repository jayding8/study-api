<?php

namespace App\Imports;

use App\Models\Logs\Logs;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class LogsImport implements ToCollection
{
    const OP_ID = 2;

    private $op_id, $op_name;

    public function __construct($params)
    {
        $this->op_id   = $params['op_id'] ?? self::OP_ID;
        $this->op_name = $params['op_name'] ?? Arr::pluck(config('kzz.op'), 'op_name', 'op_id')[$this->op_id];
    }

    public function collection(Collection $collections)
    {
        $owner = [];
        foreach ($collections as $collection) {
            if (!is_numeric($collection[1])) {
                continue;
            }
            $search = [
                'user_id' => auth()->user()->id,
                'op_id'   => $this->op_id,
                'type'    => $collection[1],
            ];
            $other  = [
                'user_name' => auth()->user()->name,
                'op_name'   => $this->op_name,
                'type_name' => $collection[0],
            ];
            Logs::updateOrCreate($search, array_merge($search, $other));
            $owner[] = intval(trim($collection[1]));
        }
        $all  = Logs::self()->condition(['op_id' => $this->op_id])->pluck('type')->toArray();
        $diff = array_diff($all, $owner);
//        logger([$all, $owner, $diff]);
        if (!empty($diff)) {
            Logs::condition(['types' => $diff])->delete();
        }
    }
}

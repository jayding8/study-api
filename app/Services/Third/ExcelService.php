<?php
/**
 * Created by PhpStorm.
 * User: jayding
 * Date: 2021/6/30
 * Time: 10:16
 */

namespace App\Services\Third;

use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Exception;

class ExcelService
{
    public function importExcele($obj, $file)
    {
        try {
            Excel::import($obj, $file);
            return true;
        } catch (Exception $e) {
            logger('importExcele Error: ', [$e->getMessage()]);
            return false;
        }
    }

    public function exportExcele()
    {
    }
}
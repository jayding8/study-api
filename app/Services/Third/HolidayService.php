<?php

/**
 * Created by PhpStorm.
 * User: jayding
 * Date: 2020/12/5
 * Time: 14:16
 */

namespace App\Services\Third;

use Holiday\Holiday;

class HolidayService
{
    public $holiday;

    public function __construct(Holiday $holiday)
    {
        $this->holiday = $holiday;
    }

    /**
     * 校验 给定的日期 是否是工作日
     *
     * @param date
     */
    public function check()
    {
        $ret = $this->holiday->check();
        // type.week值为 1~7,对应 周一 ~ 周日
        if ($ret['type']['week'] <= 5) {
            // 服务报错时,正常发送消息
            if ($ret['code'] != 0) {
                logger('holiday: source data error');
                return true;
            }
            // holiday为NULL,表示不是节假日,正常发送消息
            if (!$ret['holiday']) {
                return true;
            }
        }

        return false;
    }

}
<?php

namespace App\Contracts;

interface KzzContract
{
    // 获取第三方数据
    public function getSourceData($source, $type = 'get', $params = [], $headers = []);

    // 数据过滤(即将申购、即将上市)
    public function filterData($data);

    // 数据过滤(即将申购、即将上市)
    public function filterLowRiskData($data);

    // 发送数据
    public function sendNotice($data, $type = "actionCard");
}
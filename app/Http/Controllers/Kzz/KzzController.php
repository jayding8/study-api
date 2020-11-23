<?php

namespace App\Http\Controllers\Kzz;

use App\Http\Controllers\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use App\Contracts\KzzContract;

class KzzController extends Controller
{
    public $kzzContract;

    public function __construct(KzzContract $kzzContract)
    {
        $this->kzzContract = $kzzContract;
    }

    /**
     * 低风险策略(策略原贴:https://www.jisilu.cn/question/273614)
     * 双低计算方式:可转债价格和溢价率*100进行相加，值越小排名越排前
     *
     * @param
     * btype string 类型( 默认全部, C:可转债, E:可交换债 )
     * listed string 是否上市( 默认全部, Y:只看已上市)
     *
     */
    public function lowRiskStrategy()
    {
        $params  = request()->all();
        $headers = ['cookie' => config('kzz.header_auth')];

        // 获取第三方可转债数据
        $data = $this->kzzContract->getSourceData('jsl', 'post', $params, $headers);

        $is_notice = $params['notice_only'] ?? 0;

        // 过滤返回值
        $data_effective = $this->kzzContract->filterLowRiskData($data, intval($is_notice));

        return Response::success($data_effective);
    }

    public function strategy()
    {
        $params  = request()->all();
        $headers = ['cookie' => config('kzz.header_auth')];

        // 获取第三方可转债数据
        $data = $this->kzzContract->getSourceData('jsl', 'post', $params, $headers);

        $is_notice = $params['notice_only'] ?? 0;

        // 过滤返回值
        $data_effective = $this->kzzContract->filterLowRiskData($data, intval($is_notice));
        $return         = [];
        foreach ($data_effective as $v) {
            if ($v['price'] + 1 < $v['convert_value'] && is_numeric($v['convert_cd'])) {
                $return[] = Arr::only($v, ['bond_id', 'bond_nm', 'price', 'convert_value']);
            }
        }
        return Response::success($return);
    }
}


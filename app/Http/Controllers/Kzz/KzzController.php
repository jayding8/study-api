<?php

namespace App\Http\Controllers\Kzz;

use App\Http\Controllers\Controller;
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
     * 可申请、即将申请、新上市、即将上市 可转债
     */
    public function notice()
    {
        // 获取第三方可转债数据
        $data = $this->kzzContract->getSourceData('ths');

        if (!$data)
            return false;

        // 过滤返回值,只取待申请,待上市数据
        $data_effective = $this->kzzContract->filterData($data);

        // 发送数据
        $return = $this->kzzContract->sendNotice($data_effective);
        if ($return['errcode']) {
            logger($return);
            return Response::error($return['errcode'], $return['errmsg']);
        }

        // 检查是否有用户拥有当前转债
        $notice_data = $this->kzzContract->getForceData($data_effective);
        if ($notice_data) {
            $return = $this->kzzContract->sendNotice($notice_data, 'text');
            if ($return['errcode']) {
                logger($return);
                return Response::error($return['errcode'], $return['errmsg']);
            }
        }

        return Response::success($notice_data);
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

        if ($is_notice) {
            // 组装数据
            $notice_data = $this->kzzContract->getlowRiskStrategyData($data_effective);
            // 发送数据
            $return = $this->kzzContract->sendNotice($notice_data, 'text');
            if ($return['errcode']) {
                logger($return);
                return Response::error($return['errcode'], $return['errmsg']);
            }
        }
        return Response::success($data_effective);
    }
}


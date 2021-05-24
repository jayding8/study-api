<?php

namespace App\Services\Kzz;

use App\Contracts\KzzContract;
use App\Models\Logs\Logs;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class KzzService implements KzzContract
{
    // 双低阀值
    const DBLOW = 130;
    // 告警双低平均值
    const DBLOW_AVG_WARN = 165;
    // 清仓双低平均值
    const DBLOW_AVG_FATAL = 170;
    // 单日涨幅阈值
    const PULSE = 10;

    // 通知地址
    public $webhook;
    // 数据来源
    public $source_data;
    // 默认索引图
    public $default_indexpic;

    public $date;

    public function __construct()
    {
        $this->_init();
    }

    public function _init()
    {
        $this->webhook          = config('kzz.webhook');
        $this->source_data      = config('kzz.source_data');
        $this->default_indexpic = config('kzz.default_indexpic');
        $this->date             = Carbon::now()->toDateString();

    }

    /**
     * 获取第三方可转债数据
     */
    public function getSourceData($source, $type = 'get', $params = [], $headers = [])
    {
//        dd($this->source_data);
        if ($type == 'post') {
            return Http::withHeaders($headers)->asForm()->post($this->source_data[$source], $params)->throw()->json();
        }
        return Http::get($this->source_data[$source], $params)->throw()->json();
    }

    /**
     * 可申请、即将申请、新上市、即将上市 可转债数据结构处理
     */
    public function filterData($data)
    {
        $today_buy = $feature_buy = $today_sale = $feature_sale = [];
        foreach ($data as $v) {
            // 待申购可转债
            if ($v['apply_date'] > $this->date) {
                $feature_buy[] = $v;
            } elseif ($v['apply_date'] == $this->date) {
                $today_buy[] = $v;
            }
            // 待上市可转债
            if ($v['list_date'] > $this->date) {
                $feature_sale[] = $v;
            } elseif ($v['list_date'] == $this->date) {
                $today_sale[] = $v;
            }
        }

        return [
            'today_buy'    => $today_buy,
            'feature_buy'  => $feature_buy,
            'today_sale'   => $today_sale,
            'feature_sale' => $feature_sale,
        ];
    }

    /**
     * 集思录返回值处理
     *
     * 双低计算方式:可转债价格和溢价率*100进行相加，值越小排名越排前
     *
     * 1、去除 已发强赎、1年内到期的可转债,按照双低正序排
     * 2、双底均值大于170 或者 双低值130以下的转债消失,提示清仓可转债
     * 3、脉冲调仓:
     *      a、要有阈值,需比新标的双低值大10以上
     *      b、中位价格小于110元，则要求价格120元以上，且双低值125以上。
     *      c、中位价格大于110元，则要求价格125元以上，且双低值130以上。
     * 4、空仓建仓:双低值小于160，仓位30%；双低值小于155，仓位60%；双低值小于150，仓位100%。
     */
    public function filterLowRiskData($data, $is_notice)
    {
        $data = $data['data'];
        $data = array_filter($data, function ($item) {
            // 过滤可交换债 btype: C-可转债 E-可交换债
            if ($item['btype'] != 'C')
                return false;
            // 过滤一年内到期: maturity_dt
            if (time() + 365 * 24 * 3600 > strtotime($item['maturity_dt']))
                return false;
            // 过滤未上市的可转债
            if ($item['last_time'] === null)
                return false;
            return true;
        });
        $data = array_values($data);

        // 按照 双低 asc排序
        $data_dblow = array_values(Arr::sort($data, function ($val) {
            return $val['dblow'];
        }));

        if (!$is_notice) {
            // 如果已登录,判断当前用户是否持有
            if (auth()->check()) {
                // 自选
                $user_optional = Logs::condition(['op_id' => 2])->self()->pluck('type')->toArray();
                // 黑名单
                $user_black = Logs::condition(['op_id' => 3])->self()->pluck('type')->toArray();
                foreach ($data as &$item) {
                    $item['user_optional'] = 0;
                    $item['user_black']    = 0;
                    if (in_array($item['bond_id'], $user_optional)) {
                        $item['user_optional'] = 1;
                    }
                    if (in_array($item['bond_id'], $user_black)) {
                        $item['user_black'] = 1;
                    }
                }
//            Response::error('1001', 'Auth Faile');
            }
            return $data;
        }

        // 按照 双低 asc排序
        $data_price = array_values(Arr::sort($data, function ($val) {
            return $val['price'];
        }));

        // 获取可转债总数,判断双底平均值
        $count = count($data);
        $avg   = array_sum(array_column($data, 'dblow')) / $count;
        if ($avg > self::DBLOW_AVG_FATAL || (isset($data_dblow[0], $data_dblow[0]['dblow']) && $data_dblow[0]['dblow'] > self::DBLOW)) {
            // 双底均值大于170 或者 双低值130以下的转债消失,提示清仓可转债
            $fatal = ['avg' => $avg, 'dblow' => $data_dblow[0]['dblow']];
        } elseif ($avg > self::DBLOW_AVG_WARN) {
            // 双底均值大于165,提示减仓
            $warning = ['avg' => $avg, 'dblow' => $data_dblow[0]['dblow']];
        }
        // 脉冲调仓
        $middle = intval($count / 2);
        if ($data_price[$middle]['price'] > 110) {
            $sale_price = 125;
            $sale_dblow = 130;
        } else {
            $sale_price = 120;
            $sale_dblow = 125;
        }
        // 获取我自己的自选列表
        $owner = Logs::condition(['op_id' => 2, 'user_id' => 1])->pluck('type')->toArray();

        $owner_bond = $about_to_buy = $about_to_sale = [];
        foreach ($data_dblow as $item) {
            if (in_array($item['bond_id'], $owner)) {
                // 单价异常暴涨 或者 单价和双低都满足条件时,卖出
                if ($item['increase_rt'] > self::PULSE || ($item['price'] > $sale_price && $item['dblow'] > $sale_dblow)) {
                    $about_to_sale[] = $item;
                }
                $owner_bond[] = $item;
            } else {
                $about_to_buy[] = $item;
            }
        }
//            // 剩余规模大于10亿
//            if ($data[$i]['curr_iss_amt'] > 10) {
//                $curr_iss_amt[] = $data[$i];
//            }

        $return = [
            'owner_bond' => $owner_bond,
            'fatal'      => $fatal ?? [],
            'warning'    => $warning ?? [],
        ];
        if (!empty($about_to_sale)) {
            $return['about_to_sale'] = $about_to_sale;
            $return['about_to_buy']  = array_slice($about_to_buy, 0, 10);
        }
        return $return;
    }

    /**
     * 发送消息
     */
    public function sendNotice($data_effective, $type = 'actionCard')
    {
        switch ($type) {
            case 'actionCard':
                $data = [
                    'msgtype'    => 'actionCard',
                    'actionCard' => [
                        'title'          => "转债快报" . $this->date,
                        'text'           => $this->getActionCardText($data_effective),
                        "btnOrientation" => "0",
                        "singleTitle"    => "同花顺详情",
                        "singleURL"      => "http://data.10jqka.com.cn/ipo/bond/",
                    ],

                ];
                break;
            case 'text':
                $data = [
                    "msgtype" => "text",
                    "text"    => [
                        "content" => $data_effective['text'],
                    ],
                    "at"      => $data_effective['at']
                ];
                break;
            default:
                $data = [];
                break;
        }
        return Http::post($this->webhook, $data)->json();

    }

    /**
     * 获取actionCard text内容
     */
    private function getActionCardText($data_effective)
    {

        $today_buy    = $this->getStr($data_effective['today_buy'], 'buy');
        $feature_buy  = $this->getStr($data_effective['feature_buy'], 'buy');
        $today_sale   = $this->getStr($data_effective['today_sale'], 'sale');
        $feature_sale = $this->getStr($data_effective['feature_sale'], 'sale');

        $actionCardText = "![screenshot](" . $this->default_indexpic . ")    
 ### 今日可申请      
 " . $today_buy . "
 
 ### 即将申购    
 " . $feature_buy . "
 
 ### 今日上市   
 " . $today_sale . "  
  
 ### 即将上市   
 " . $feature_sale;

        return $actionCardText;
    }

    /**
     * 获取 text内容
     */
    public function getlowRiskStrategyData($data_effective)
    {
        if ($data_effective['fatal']) {
            $text = "【转债快报】" . PHP_EOL .
                "双低均值: " . $data_effective['fatal']['avg'] . PHP_EOL .
                "最小双低: " . $data_effective['fatal']['dblow'] . PHP_EOL .
                "建议清仓可转债!!!";
        } else {
            $text = "【转债快报】" . PHP_EOL;
            if ($data_effective['warning']) {
                $text .= "双低均值: " . $data_effective['warning']['avg'] . PHP_EOL .
                    "建议减仓!!!" . PHP_EOL . PHP_EOL;
            }
            if (isset($data_effective['about_to_sale'])) {
                $about_to_sale = $this->getStrJsl($data_effective['about_to_sale']);
                $about_to_buy  = $this->getStrJsl($data_effective['about_to_buy']);

                $text .= "推荐卖出: " . PHP_EOL .
                    $about_to_sale . PHP_EOL .
                    "推荐买入: " . PHP_EOL .
                    $about_to_buy;
            }
        }


        $at = [
            "atMobiles" => ["15251895379"],
            "isAtAll"   => false
        ];
        return ['text' => $text, 'at' => $at];
    }

    /**
     * 获取 text内容
     */
    public function getForceData($data_effective)
    {
        if (!isset($data_effective['today_sale']) || empty($data_effective['today_sale'])) {
            return false;
        }

        $logs = $owner = [];
        foreach ($data_effective['today_sale'] as $v) {
            $log  = Logs::condition(['op_id' => 1])->where(function ($query) use ($v) {
                $query->where('type', $v['bond_id']);
                $query->orWhere('type_name', $v['bond_nm']);
            })->with('user')->get()->toArray();
            $logs = array_merge($logs, $log);
        }

        $text = "【转债快报】您有中签转债今日上市,请及时关注" . PHP_EOL;

        $at = [
            "atMobiles" => Arr::pluck($logs, 'user.phone'),
            "isAtAll"   => false
        ];
        if (!$at['atMobiles']) {
            return false;
        }
        return ['text' => $text, 'at' => $at];
    }

    /**
     * 拼接字符串
     */
    private function getStr($arr, $type = 'buy')
    {
        $return = '';
        $buy    = '债券名称&nbsp;申购代码&nbsp;申购日期&nbsp;   
        ';
        $sale   = '债券名称&nbsp;债券代码&nbsp;正股代码&nbsp;上市日期&nbsp;   
        ';
        if (!empty($arr)) {
            foreach ($arr as $v) {
                if ($type == 'buy') {
                    $return .= $v['bond_nm'] . '&nbsp;' . $v['apply_cd'] . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . change_date_format($v['apply_date'], 'm-d') . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  
            ';
                } elseif ($type == 'sale') {
                    $return .= $v['bond_nm'] . '&nbsp;' . $v['bond_id'] . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $v['stock_id'] . '&nbsp;&nbsp;&nbsp;&nbsp;' . change_date_format($v['list_date'], 'm-d') . '&nbsp;  
            ';
                }

            }
        }

        $return = $return ? $$type . $return : '暂无';
        return $return;
    }

    private function getStrJsl($data)
    {
        $return = '';
        if (empty($data)) {
            return '暂无数据' . PHP_EOL;
        }
        $return .= '代码 转债名称 现价 溢价率 双低' . PHP_EOL;
        foreach ($data as $v) {
            $return .= $v['bond_id'] . '   ' . $v['bond_nm'] . '   ' . $v['price'] . '   ' . $v['premium_rt'] . '   ' . $v['dblow'] . PHP_EOL;
        }
        return $return;
    }

}

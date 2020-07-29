<?php

namespace App\Services\Kzz;

use App\Contracts\KzzContract;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class KzzService implements KzzContract
{
    // 通知地址
    public $webhook;
    // 数据来源
    public $source_data;
    // 默认索引图
    public $default_indexpic;

    public $owner;

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
        $this->owner            = config('kzz.owner');
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
     * 同花顺返回值处理
     */
    public function filterData($data)
    {
        $today_buy = $feature_buy = $today_sale = $feature_sale = [];
        foreach ($data['list'] as $v) {
            // 待申购可转债
            if ($v['sub_date'] > $this->date) {
                $feature_buy[] = $v;
            } elseif ($v['sub_date'] == $this->date) {
                $today_buy[] = $v;
            }
            // 待上市可转债
            if ($v['listing_date'] > $this->date) {
                $feature_sale[] = $v;
            } elseif ($v['listing_date'] == $this->date) {
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
     * 1、去除 已发强赎、1年内到期的可转债,按照双低正序排,计划取 前10% 的可转债作为底仓
     * 2、持有转债跌出 20% 后直接卖出
     * 3、待定
     */
    public function filterLowRiskData($data)
    {
        // 按照 双低 asc排序
        $data = array_values(Arr::sort($data['rows'], function ($val) {
            return $val['cell']['dblow'];
        }));

        // 获取可转债总数,取 前10% 作为舱底
        $count = count($data);
        $buy   = intval($count / 10);
        $sale  = intval($count / 5);

        $return          = [];
        $about_to_expire = $owner_bond = $about_to_sale = $curr_iss_amt = [];
        for ($i = 0; $i < $buy; $i++) {
            // 标注 1年内到期 的可转债
            if (strtotime($data[$i]['cell']['maturity_dt']) < time() + 365 * 24 * 3600) {
                $about_to_expire[] = $data[$i]['cell'];
            }

            // 剩余规模大于10亿
            if (strtotime($data[$i]['cell']['curr_iss_amt']) > 10) {
                $curr_iss_amt[] = $data[$i]['cell'];
            }

            // 标注持仓可转债
            if (in_array($data[$i]['id'],$this->owner)) {
                $owner_bond[] = $data[$i]['cell'];
            }

            $return[] = $data[$i]['cell'];
        }

        for ($i = 0; $i < $sale; $i++) {
            // 标注持仓可转债
            $key = array_search($data[$i]['id'], $this->owner);
            if (is_numeric($key)) {
                unset($this->owner[$key]);
            }
        }

        // 获取 跌出前20%的 已持有 可转债
        foreach ($data as $v) {
            if (in_array($v['id'], $this->owner)) {
                $about_to_sale[] = $v['cell'];
            }
        }

        return [
            'return'          => $return,
            'about_to_expire' => $about_to_expire,
            'owner_bond'      => $owner_bond,
            'about_to_sale'   => $about_to_sale,
            'curr_iss_amt'    => $curr_iss_amt,
        ];
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
                        "content" => $this->getText($data_effective),
                    ],
                    "at"      => [
                        "atMobiles" => [
                            "15251895379",
                        ],
                        "isAtAll"   => false
                    ]
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
    private function getText($data_effective)
    {

        $return          = $this->getStrJsl($data_effective['return']);
        $about_to_expire = $this->getStrJsl($data_effective['about_to_expire']);
        $owner_bond      = $this->getStrJsl($data_effective['owner_bond']);
        $about_to_sale   = $this->getStrJsl($data_effective['about_to_sale']);
        $curr_iss_amt    = $this->getStrJsl($data_effective['curr_iss_amt']);

        $text = "【转债快报】" . PHP_EOL .
            "今日 前10% 推荐: " . PHP_EOL .
            $return . PHP_EOL .
            "一年内即将到期: " . PHP_EOL .
            $about_to_expire . PHP_EOL .
            "已拥有: " . PHP_EOL .
            $owner_bond . PHP_EOL .
            "推荐卖出: " . PHP_EOL .
            $about_to_sale . PHP_EOL .
            "剩余规模大于10亿: " . PHP_EOL .
            $curr_iss_amt;

        return $text;
    }

    /**
     * 拼接字符串
     */
    private function getStr($arr, $type = 'buy')
    {
        $return = '';
        $buy    = '债券名称&nbsp;申购代码&nbsp;申购日期&nbsp;中签公布&nbsp;   
        ';
        $sale   = '债券名称&nbsp;债券代码&nbsp;正股代码&nbsp;上市日期&nbsp;   
        ';
        if (!empty($arr)) {
            foreach ($arr as $v) {
                if ($type == 'buy') {
                    $return .= $v['bond_name'] . '&nbsp;' . $v['sub_code'] . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . change_date_format($v['sub_date'], 'm-d') . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . change_date_format($v['sign_date'], 'm-d') . '&nbsp;  
            ';
                } elseif ($type == 'sale') {
                    $return .= $v['bond_name'] . '&nbsp;' . $v['bond_code'] . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $v['code'] . '&nbsp;&nbsp;&nbsp;&nbsp;' . change_date_format($v['listing_date']) . '&nbsp;  
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

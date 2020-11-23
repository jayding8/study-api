<?php

namespace App\Services\Kzz;

use App\Contracts\KzzContract;
use App\Models\Logs\Logs;
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
     * 1、去除 已发强赎、1年内到期的可转债,按照双低正序排,计划取 前10% 的可转债作为底仓
     * 2、持有转债跌出 20% 后直接卖出
     * 3、待定
     */
    public function filterLowRiskData($data, $is_notice)
    {
        // 按照 双低 asc排序
        $data = array_values(Arr::sort($data['rows'], function ($val) {
            return $val['cell']['dblow'];
        }));
        if (!$is_notice) {
            $data = array_column($data, 'cell');
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
        // 获取可转债总数,取 前10% 作为舱底
        $count = count($data);
        $buy   = intval($count / 10);
        $sale  = intval($count / 5);
        // 获取我自己的自选列表
        $owner = Logs::condition(['op_id' => 2,'user_id' => 1])->pluck('type')->toArray();

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
            if (in_array($data[$i]['id'], $owner)) {
                $owner_bond[] = $data[$i]['cell'];
            }

            $return[] = $data[$i]['cell'];
        }

        for ($i = 0; $i < $sale; $i++) {
            // 标注持仓可转债
            $key = array_search($data[$i]['id'], $owner);
            if (is_numeric($key)) {
                unset($owner[$key]);
            }
        }

        // 获取 跌出前20%的 已持有 可转债
        foreach ($data as $v) {
            if (in_array($v['id'], $owner)) {
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
        $buy    = '债券名称&nbsp;申购代码&nbsp;申购日期&nbsp;申购建议&nbsp;   
        ';
        $sale   = '债券名称&nbsp;债券代码&nbsp;正股代码&nbsp;上市日期&nbsp;   
        ';
        if (!empty($arr)) {
            foreach ($arr as $v) {
                if ($type == 'buy') {
                    $return .= $v['bond_nm'] . '&nbsp;' . $v['apply_cd'] . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . change_date_format($v['apply_date'], 'm-d') . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $v['jsl_advise_text'] ?: '暂无' . '&nbsp;  
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

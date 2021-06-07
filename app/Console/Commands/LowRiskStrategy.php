<?php

namespace App\Console\Commands;

use App\Contracts\KzzContract;
use Illuminate\Console\Command;
use App\Services\Third\HolidayService;

class LowRiskStrategy extends Command
{
    /**
     * The name and signature of the console command.
     * lrs: lowRiskStrategy 低风险策略
     * @var string
     */
    protected $signature = 'send:lrs';

    const NOTICE = 1;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send lowRiskStrategy notice to dingtalk';

    public $kzzContract, $holidayService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(KzzContract $kzzContract, HolidayService $holidayService)
    {
        parent::__construct();
        $this->kzzContract    = $kzzContract;
        $this->holidayService = $holidayService;
    }

    /**
     * Execute the console command.
     *
     * 低风险策略(策略原贴:https://www.jisilu.cn/question/273614)
     * 双低计算方式:可转债价格和溢价率*100进行相加，值越小排名越排前
     *
     * @param
     * btype string 类型( 默认全部, C:可转债, E:可交换债 )
     * listed string 是否上市( 默认全部, Y:只看已上市)
     *
     * @return mixed
     */
    public function handle()
    {
        // 校验是否是法定工作日
        if (!$this->holidayService->check()) {
//            logger('notice-holiday-check:', ['today is holiday / weekend']);
            $this->error('today is holiday / weekend');
            return false;
        }
        // 获取第三方可转债数据
        $data = $this->kzzContract->getSourceData('jsl_new');
        // 过滤返回值
        $data_effective = $this->kzzContract->filterLowRiskData($data, self::NOTICE);
        if (!$data_effective['fatal'] && !$data_effective['warning'] && !isset($data_effective['about_to_sale'])) {
            return false;
        }
        // 组装数据
        $notice_data = $this->kzzContract->getlowRiskStrategyData($data_effective);
        // 发送数据
        $return = $this->kzzContract->sendNotice($notice_data, 'text');
        if ($return['errcode']) {
            logger('lowRiskStrategy:', [$return]);
            $this->error($return['errcode'] . ':' . $return['errmsg']);
            return false;
        }
//        logger($notice_data);
        $this->info('sucess');
        return true;
    }
}

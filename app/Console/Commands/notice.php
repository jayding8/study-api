<?php

namespace App\Console\Commands;

use App\Contracts\KzzContract;
use App\Services\Third\HolidayService;
use Illuminate\Console\Command;

class Notice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:notice';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send notice to dingtalk';

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
     * 可申请、即将申请、新上市、即将上市 可转债
     *
     * @return mixed
     */
    public function handle()
    {
        // 校验是否是法定工作日
        if (!$this->holidayService->check()) {
            logger('notice-holiday-check:', ['today is holiday / weekend']);
            $this->error('today is holiday / weekend');
            return false;
        }
        // 获取第三方可转债数据
        $data = $this->kzzContract->getSourceData('jsl_coming');
        if (!$data || !isset($data['data'])) {
            logger('notice-getData:', ['jsl source data error(coming)']);
            $this->error('jsl source data error(coming)');
            return false;
        }
        // 过滤返回值,只取待申请,待上市数据
        $data_effective = $this->kzzContract->filterData($data['data']);
        // 发送数据
        $return = $this->kzzContract->sendNotice($data_effective);
        if ($return['errcode']) {
            logger('notice-notice:', [$return]);
            $this->error($return['errcode'] . ':' . $return['errmsg']);
            return false;
        }

        // 检查是否有用户拥有当前转债
        $notice_data = $this->kzzContract->getForceData($data_effective);
        if ($notice_data) {
            $return = $this->kzzContract->sendNotice($notice_data, 'text');
            if ($return['errcode']) {
                logger('notice-owner:', [$return]);
                $this->error($return['errcode'] . ':' . $return['errmsg']);
                return false;
            }
        }
        $this->info('sucess');
        return true;
    }
}

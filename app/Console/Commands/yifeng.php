<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Contracts\KzzContract;

class YiFeng extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:yifeng';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send notice to dingtalk';

    public $kzzContract;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(KzzContract $kzzContract)
    {
        parent::__construct();
        $this->kzzContract = $kzzContract;
    }

    /**
     * Execute the console command.
     * 可申请、即将申请、新上市、即将上市 可转债
     *
     * @return mixed
     */
    public function handle()
    {
        // 获取第三方可转债数据
        $data = $this->kzzContract->getSourceData('yifeng', 'post', [
            "page"=> 1,
            "spuId" => 279,
            "skuId" => 392,
            "size"=> 20,
            "resourceId"=> 5,
            "orderBy"=> "SORT_DESC"
          ], [], 'json');
        //   var_dump($data);
        if (!$data || !isset($data['code']) || $data['code'] != 0) {
            logger('yifeng:', ['source data error(coming)']);
            $this->error('yifeng source data error(coming)');
            return false;
        }

        // $hasInv = array_filter($data['data']['list'], function($item) {
        //     return $item['hasInv'];
        // } );
        // var_dump($hasInv);
        if (!$data['data']['inventory'] ) {
            logger('yifeng:', ['zan wu shang xin']);
            $this->error('yifeng zan wu shang xin');
            return false;
        }

        // 发送数据
        $return = $this->kzzContract->sendNotice($data['data'], 'yifeng');
        if ($return['errcode']) {
            logger('notice-notice:', [$return]);
            $this->error($return['errcode'] . ':' . $return['errmsg']);
            return false;
        }
        $this->info('sucess');
        return true;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class notice extends Command
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

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Http::get('http://ding-api.study.com/kzz/kzz')->throw()->json();
//        Http::get('http://ding-api.study.com/kzz/lowRiskKzz?notice_only=1&is_search=Y&rp=50&listed=Y&btype=C')->throw()->json();
    }
}

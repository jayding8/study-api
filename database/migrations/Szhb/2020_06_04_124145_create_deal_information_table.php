<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDealInformationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deal_information', function (Blueprint $table) {
            $table->increments('id')->comment('主键ID');
            $table->string('type')->comment('类型:BTC,ETH,EOS');
            $table->string('op_type')->comment('操作类型:买,卖');
            $table->decimal('buy_price',10,5)->comment('购买时价格');
            $table->decimal('buy_num',10,5)->comment('购买数量');
            $table->decimal('buy_money',10,5)->comment('认购资金');
            $table->decimal('sale_price',10,5)->comment('出售价格');
            $table->decimal('sale_num',10,5)->comment('出售数量');
            $table->decimal('sale_money',10,5)->comment('出售资金');
            $table->tinyInteger('is_used')->default(0)->comment('是否使用过');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deal_information');
    }
}

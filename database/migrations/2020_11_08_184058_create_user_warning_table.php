<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserWarningTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_warning', function (Blueprint $table) {
            $table->increments('id')->comment('主键ID');
            $table->integer('or_id')->comment('operating_record表主键');
            $table->integer('user_id')->comment('用户ID');
            $table->string('user_name')->comment('用户名');
            $table->integer('type')->comment('类型ID');
            $table->string('type_name')->comment('类型名');
            $table->float('up', 7, 3)->nullable()->comment('价格涨到');
            $table->float('down', 7, 3)->nullable()->comment('价格跌到');
            $table->tinyInteger('percent')->nullable()->comment('涨跌幅');
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
        Schema::dropIfExists('user_warning');
    }
}

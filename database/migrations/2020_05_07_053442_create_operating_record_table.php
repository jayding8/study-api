<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOperatingRecordTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('operating_record', function (Blueprint $table) {
            $table->increments('id')->comment('主键ID');
            $table->integer('user_id')->comment('用户ID');
            $table->string('user_name')->comment('用户名');
            $table->integer('op_id')->comment('操作ID');
            $table->string('op_name')->comment('操作名');
            $table->integer('type')->comment('类型ID');
            $table->string('type_name')->comment('类型名');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('operating_record');
    }
}

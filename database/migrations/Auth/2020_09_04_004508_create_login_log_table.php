<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoginLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('login_log', function (Blueprint $table) {
            $table->integer('user_id')->comment('用户ID');
            $table->string('user_name')->comment('用户名');
            $table->string('token')->comment('用户token');
            $table->string('ip_address', 45)->nullable()->comment('IP地址');
            $table->string('extension')->comment('自定义信息');
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
        Schema::dropIfExists('login_log');
    }
}

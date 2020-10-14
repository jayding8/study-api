<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->integer('user_id')->comment('用户ID')->unique();
            $table->string('user_name')->comment('用户名');
            $table->string('access_token')->comment('用户token')->unique();
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
        Schema::dropIfExists('sessions');
    }
}

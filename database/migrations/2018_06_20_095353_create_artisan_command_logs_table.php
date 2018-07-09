<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateArtisanCommandLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('artisan_command_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->char('command_sn', 50)->comment('编号');
            $table->char('title', 50)->comment('标题');
            $table->string('description')->default('')->comment('说明');
            $table->string('options')->default('')->comment('选项');
            $table->unsignedSmallInteger('status')->default(0)->comment('状态 0-未完成 1-已完成 2-失败');
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
        Schema::dropIfExists('artisan_command_logs');
    }
}

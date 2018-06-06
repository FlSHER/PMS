<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventCarbonCopyLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('event_carbon_copy_logs', function (Blueprint $table) {
            $table->unsignedInteger('event_log_id')->comment('事件日志ID');
            $table->unsignedMediumInteger('addressee_sn')->comment('抄送人人编号');
            $table->char('addressee_name', 10)->comment('抄送人人姓名');

            $table->foreign('event_log_id')->references('id')->on('event_logs');
            $table->index('addressee_sn');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('event_carbon_copy_logs');
    }
}

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
        Schema::create('event_carbon_copy_log', function (Blueprint $table) {
            $table->unsignedInteger('event_log_id')->comment('事件日志ID');
            $table->string('addressee_sn')->comment('抄送人人编号');
            $table->string('addressee_name')->comment('抄送人人姓名');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('event_carbon_copy_log');
    }
}

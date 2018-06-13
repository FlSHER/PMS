<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventLogParticipantTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('event_log_participants', function (Blueprint $table) {
            $table->unsignedInteger('event_log_id')->comment('事件日志ID');
            $table->unsignedMediumInteger('staff_sn')->comment('事件参与人编号');
            $table->char('staff_name', 10)->comment('事件参与人姓名');

            $table->foreign('event_log_id')->references('id')->on('event_logs');
            $table->index('staff_sn');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('event_log_participants');
    }
}

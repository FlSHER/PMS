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
        Schema::create('event_log_participant', function (Blueprint $table) {
            $table->unsignedInteger('event_log_id')->comment('事件日志ID');
            $table->string('participant_sn')->comment('事件参与人编号');
            $table->string('participant_name')->comment('事件参与人姓名');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('event_log_participant');
    }
}

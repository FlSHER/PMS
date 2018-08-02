<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventLogAddresseesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('event_log_addressees', function (Blueprint $table) {
            $table->unsignedInteger('event_log_group_id')->comment('事件日志ID');
            $table->unsignedMediumInteger('staff_sn')->comment('抄送人人编号');
            $table->char('staff_name', 10)->comment('抄送人人姓名');

            $table->foreign('event_log_group_id')->references('id')->on('event_log_groups');
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
        Schema::dropIfExists('event_log_addressees');
    }
}

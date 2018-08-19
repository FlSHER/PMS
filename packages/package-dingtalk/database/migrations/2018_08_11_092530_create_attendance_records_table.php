<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAttendanceRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->increments('id');
            $table->string('userId', 50)->commit('用户ID');
            $table->unsignedInteger('groupId')->commit('考勤组ID');
            $table->dateTime('workDate')->nullable()->commit('工作日期');
            $table->dateTime('baseOnTime')->nullable()->commit('上班时间');
            $table->dateTime('baseOffTime')->nullable()->commit('下班时间');
            $table->dateTime('userOnTime')->nullable()->commit('实际上班打卡时间');
            $table->dateTime('userOffTime')->nullable()->commit('实际下班打卡时间');
            $table->dateTime('restBeginTime')->nullable()->commit('午休开始时间');
            $table->dateTime('restEndTime')->nullable()->commit('午休结束时间');
            $table->string('worktime', 10)->nullable()->commit('工作时长');
            $table->string('latetime', 10)->nullable()->commit('迟到时长');
            $table->string('overtime', 10)->nullable()->commit('加班时长');
            $table->string('leavetime', 10)->nullable()->commit('请假时长');
            $table->string('earlytime', 10)->nullable()->commit('早退时长');
            
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
        Schema::dropIfExists('attendance_records');
    }
}
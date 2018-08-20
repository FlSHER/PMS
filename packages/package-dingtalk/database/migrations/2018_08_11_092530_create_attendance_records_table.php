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
            $table->string('userId', 50)->comment('用户ID');
            $table->unsignedMediumInteger('staff_sn')->default(0)->comment('员工编号');
            $table->char('staff_name', 10)->nullable()->comment('员工姓名');
            $table->unsignedInteger('groupId')->comment('考勤组ID');
            $table->dateTime('workDate')->nullable()->comment('工作日期');
            $table->dateTime('baseOnTime')->nullable()->comment('上班时间');
            $table->dateTime('baseOffTime')->nullable()->comment('下班时间');
            $table->dateTime('userOnTime')->nullable()->comment('实际上班打卡时间');
            $table->dateTime('userOffTime')->nullable()->comment('实际下班打卡时间');
            $table->dateTime('restBeginTime')->nullable()->comment('午休开始时间');
            $table->dateTime('restEndTime')->nullable()->comment('午休结束时间');
            $table->string('worktime', 10)->nullable()->comment('工作时长');
            $table->string('latetime', 10)->nullable()->comment('迟到时长');
            $table->string('overtime', 10)->nullable()->comment('加班时长');
            $table->string('leavetime', 10)->nullable()->comment('请假时长');
            $table->string('earlytime', 10)->nullable()->comment('早退时长');
            
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

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePointManagementTargetLogHasStaffTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('point_management_target_log_has_staff', function (Blueprint $table) {
            $table->unsignedInteger('target_id')->comment('奖扣指标ID');
            $table->unsignedInteger('target_log_id')->comment('奖扣指标日志ID');
            $table->timestamp('date')->comment('年月（其余默认为0或1）');
            $table->unsignedMediumInteger('staff_sn')->comment('员工编号');
            $table->char('staff_name', 10)->comment('员工姓名');
            $table->unsignedTinyInteger('brand_id')->comment('品牌ID');
            $table->char('brand_name', 10)->comment('品牌名称');
            $table->unsignedSmallInteger('department_id')->comment('部门ID');
            $table->char('department_name', 100)->comment('部门名称');
            $table->char('shop_sn', 10)->comment('店铺代码');
            $table->char('shop_name', 50)->comment('店铺名称');
            $table->unsignedInteger('point_b_awarding_result')->comment('奖分完成情况');
            $table->unsignedInteger('point_b_deducting_result')->comment('扣分完成情况');
            $table->unsignedInteger('event_count_result')->comment('奖扣次数完成情况');
            $table->decimal('deducting_percentage_result', 5, 2)->comment('扣分比例完成情况,n%');

            $table->primary(['target_log_id', 'staff_sn'], 'target_log_id_staff_sn_primary');
            $table->index('date');
            $table->foreign('target_id')->references('id')->on('point_management_targets');
            $table->foreign('target_log_id')->references('id')->on('point_management_target_logs');
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
        Schema::dropIfExists('point_management_target_log_has_staff');
    }
}

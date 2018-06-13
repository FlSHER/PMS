<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePointLogTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('point_log_sources', function (Blueprint $table) {
            $table->tinyInteger('id')->default(0);
            $table->char('name', 10);

            $table->unique('id');
        });

        Schema::create('point_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->char('title', 50)->comment('标题');
            $table->unsignedMediumInteger('staff_sn')->comment('员工编号');
            $table->char('staff_name', 10)->comment('员工姓名');
            $table->unsignedTinyInteger('brand_id')->comment('品牌ID');
            $table->char('brand_name', 10)->comment('品牌名称');
            $table->unsignedSmallInteger('department_id')->comment('部门ID');
            $table->char('department_name', 100)->comment('部门名称');
            $table->char('shop_sn', 10)->comment('店铺代码');
            $table->char('shop_name', 50)->comment('店铺名称');
            $table->mediumInteger('point_a')->default(0)->comment('A分变化');
            $table->mediumInteger('point_b')->default(0)->comment('B分变化');
            $table->timestamp('changed_at')->comment('积分变化时间');
            $table->tinyInteger('source_id')->default(0)->comment('积分来源');
            $table->unsignedInteger('source_foreign_key')->nullable()->comment('来源关联ID');
            $table->unsignedMediumInteger('first_approver_sn')->nullable()->comment('初审人编号');
            $table->char('first_approver_name', 10)->default('')->comment('初审人姓名');
            $table->unsignedMediumInteger('final_approver_sn')->nullable()->comment('终审人编号');
            $table->char('final_approver_name', 10)->default('')->comment('终审人姓名');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('source_id')->references('id')->on('point_log_sources');
        });

        Schema::create('personal_point_statistic_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedMediumInteger('staff_sn')->comment('员工编号');
            $table->char('staff_name', 10)->comment('员工姓名');
            $table->unsignedTinyInteger('brand_id')->comment('品牌ID');
            $table->char('brand_name', 10)->comment('品牌名称');
            $table->unsignedSmallInteger('department_id')->comment('部门ID');
            $table->char('department_name', 100)->comment('部门名称');
            $table->char('shop_sn', 10)->comment('店铺代码');
            $table->char('shop_name', 50)->comment('店铺名称');
            $table->timestamp('date')->comment('年月（其余默认为0）');
            $table->mediumInteger('point_a')->comment('A分');
            $table->mediumInteger('point_b_monthly')->comment('当月B分');
            $table->mediumInteger('point_b_total')->comment('累计B分');
            $table->timestamps();
        });

        Schema::create('personal_point_statistics', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedMediumInteger('staff_sn')->comment('员工编号');
            $table->mediumInteger('point_a')->comment('A分');
            $table->mediumInteger('point_b_monthly')->comment('当月B分');
            $table->mediumInteger('point_b_total')->comment('累计B分');
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
        Schema::dropIfExists('point_logs');
        Schema::dropIfExists('point_log_sources');
        Schema::dropIfExists('personal_point_statistics');
        Schema::dropIfExists('personal_point_statistic_logs');
    }
}

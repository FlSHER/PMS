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
        Schema::create('point_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->char('title', 50)->comment('标题');
            $table->unsignedMediumInteger('staff_sn')->comment('员工编号');
            $table->char('staff_name', 10)->comment('员工姓名');
            $table->unsignedTinyInteger('brand_id')->comment('品牌ID');
            $table->char('brand_name', 10)->comment('品牌名称');
            $table->unsignedSmallInteger('department_id')->comment('部门ID');
            $table->char('department_name', 100)->comment('部门名称');
            $table->char('shop_sn', 10)->nullable()->default('')->comment('店铺代码');
            $table->char('shop_name', 50)->nullable()->default('')->comment('店铺名称');
            $table->mediumInteger('point_a')->default(0)->comment('A分变化');
            $table->mediumInteger('point_b')->default(0)->comment('B分变化');
            $table->dateTime('changed_at')->nullable()->comment('积分变化时间');
            $table->tinyInteger('source_id')->default(0)->comment('积分来源');
            $table->unsignedInteger('source_foreign_key')->nullable()->comment('来源关联ID');
            $table->unsignedMediumInteger('first_approver_sn')->nullable()->comment('初审人编号');
            $table->char('first_approver_name', 10)->default('')->comment('初审人姓名');
            $table->unsignedMediumInteger('final_approver_sn')->nullable()->comment('终审人编号');
            $table->char('final_approver_name', 10)->default('')->comment('终审人姓名');
            $table->unsignedSmallInteger('is_revoke')->default(0)->comment('是否撤回记录 0-否 1-是');
            $table->timestamps();
            $table->softDeletes();
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
    }
}

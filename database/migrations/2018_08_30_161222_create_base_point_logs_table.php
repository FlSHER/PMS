<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBasePointLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('base_point_logs', function (Blueprint $table) {
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
            $table->mediumInteger('point_b')->default(0)->comment('基础积分');
            $table->char('type', 20)->nullable()->comment('记录分类: baseEdu-学历分结算');

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
        Schema::dropIfExists('base_point_logs');
    }
}

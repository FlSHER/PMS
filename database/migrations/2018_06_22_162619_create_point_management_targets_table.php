<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePointManagementTargetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('point_management_targets', function (Blueprint $table) {
            $table->increments('id');
            $table->char('name', 20)->comment('指标名称');
            $table->unsignedInteger('point_b_awarding_target')->comment('奖分指标');
            $table->unsignedInteger('point_b_deducting_target')->comment('扣分指标');
            $table->unsignedInteger('event_count_target')->comment('奖扣次数指标');
            $table->decimal('deducting_percentage_target', 5, 2)->comment('扣分比例指标,n%');
            $table->string('point_b_awarding_coefficient',10)->comment('奖分未完成扣分');
            $table->string('point_b_deducting_coefficient',10)->comment('扣分未完成扣分');
            $table->string('event_count_mission',10)->comment('未完成扣分人次');
            $table->string('deducting_percentage_ratio',10)->comment('未完成扣分');
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
        Schema::dropIfExists('point_management_targets');
    }
}

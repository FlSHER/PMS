<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePointManagementTargetLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('point_management_target_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('target_id')->comment('奖扣指标ID');
            $table->timestamp('date')->comment('年月（其余默认为0或1）');
            $table->unsignedInteger('point_b_awarding_target')->comment('奖分指标');
            $table->unsignedInteger('point_b_deducting_target')->comment('扣分指标');
            $table->unsignedInteger('event_count_target')->comment('奖扣次数指标');
            $table->decimal('deducting_percentage_target', 5, 2)->comment('扣分比例指标,n%');

            $table->foreign('target_id')->references('id')->on('point_management_targets');
            $table->index('date');
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
        Schema::dropIfExists('point_management_target_logs');
    }
}

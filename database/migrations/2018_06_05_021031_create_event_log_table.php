<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('event_log', function (Blueprint $table) {
            $table->increments('id');
            $table->string('event_name')->nullable()->default('')->comment('事件名称');
            $table->string('description')->nullable()->default('')->comment('事件说明');
            $table->unsignedInteger('event_id')->comment('事件ID');
            $table->unsignedTinyInteger('event_type_id')->comment('事件类型ID');
            $table->unsignedInteger('point_a')->nullable()->default('')->comment('A分变化');
            $table->unsignedInteger('point_b')->nullable()->default('')->comment('B分变化');
            $table->unsignedInteger('count')->nullable()->default(0)->comment('完成次数');
            $table->string('first_approver_sn')->nullable()->default('')->comment('初审人编号');
            $table->string('first_approver_name')->nullable()->default('')->comment('初审人姓名');
            $table->string('first_approver_remark')->nullable()->default('')->comment('初审人备注');
            $table->timestamp('first_approver_at')->nullable()->default(null)->comment('初审通过时间');
            $table->string('final_approver_sn')->nullable()->default('')->comment('终审人编号');
            $table->string('final_approver_name')->nullable()->default('')->comment('终审人姓名');
            $table->string('final_approver_remark')->nullable()->default('')->comment('终审人备注');
            $table->timestamp('final_approver_at')->nullable()->default(null)->comment('终审通过时间');
            $table->string('rejecter_sn')->nullable()->default('')->comment('驳回人编号');
            $table->string('rejecter_name')->nullable()->default('')->comment('驳回人姓名');
            $table->timestamp('rejected_at')->nullable()->default(null)->comment('驳回时间');
            $table->string('reject_remark')->nullable()->default('')->comment('驳回备注');
            $table->string('recorder_sn')->nullable()->default('')->comment('记录人编号');
            $table->string('recorder_name')->nullable()->default('')->comment('记录人姓名');
            $table->unsignedTinyInteger('status_id')->nullable()->default(0)->comment('状态ID');
            $table->timestamp('executed_at')->nullable()->default(null)->comment('执行时间');

            $table->index('event_id');
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
        Schema::dropIfExists('event_log');
    }
}

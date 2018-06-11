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
        Schema::create('event_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('event_id')->comment('事件ID');
            $table->unsignedSmallInteger('event_type_id')->comment('事件类型ID');
            $table->char('event_name', 50)->comment('事件名称');
            $table->char('description', 255)->default('')->comment('事件说明');
            $table->mediumInteger('point_a')->default(0)->comment('A分变化');
            $table->mediumInteger('point_b')->default(0)->comment('B分变化');
            $table->unsignedMediumInteger('count')->default(1)->comment('完成次数');
            $table->unsignedMediumInteger('first_approver_sn')->comment('初审人编号');
            $table->char('first_approver_name', 10)->comment('初审人姓名');
            $table->char('first_approve_remark', 255)->default('')->comment('初审人备注');
            $table->timestamp('first_approved_at')->nullable()->comment('初审通过时间');
            $table->unsignedMediumInteger('final_approver_sn')->comment('终审人编号');
            $table->char('final_approver_name', 10)->comment('终审人姓名');
            $table->char('final_approve_remark', 255)->default('')->comment('终审人备注');
            $table->timestamp('final_approved_at')->nullable()->comment('终审通过时间');
            $table->unsignedMediumInteger('rejecter_sn')->nullable()->comment('驳回人编号');
            $table->char('rejecter_name', 10)->nullable()->comment('驳回人姓名');
            $table->timestamp('rejected_at')->nullable()->comment('驳回时间');
            $table->char('reject_remark', 255)->default('')->comment('驳回备注');
            $table->unsignedMediumInteger('recorder_sn')->comment('记录人编号');
            $table->char('recorder_name', 10)->comment('记录人姓名');
            $table->tinyInteger('status_id')->default(0)->comment('状态ID 0:待审核 1:初审通过 2:终审通过 -1:驳回');
            $table->timestamp('executed_at')->nullable()->comment('执行时间');
            $table->mediumInteger('recorder_point')->default(0)->comment('记录人得分');
            $table->mediumInteger('first_approver_point')->default(0)->comment('初审人得分');
            $table->timestamps();
            /* 索引和外键 */
            $table->foreign('event_id')->references('id')->on('events');
            $table->foreign('event_type_id')->references('id')->on('event_types');
            $table->index('first_approver_sn');
            $table->index('final_approver_sn');
            $table->index('rejecter_sn');
            $table->index('recorder_sn');
            $table->index('status_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('event_logs');
    }
}

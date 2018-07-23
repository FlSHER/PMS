<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventLogGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('event_log_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->char('title', 50)->comment('标题');
            $table->char('remark', 255)->default('')->comment('备注');
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
            $table->tinyInteger('status_id')->default(0)->comment('状态ID 0:待审核 1:初审通过 2:终审通过 -1:驳回 -2:撤回 -3:撤销');
            $table->timestamp('executed_at')->nullable()->comment('执行时间');
            $table->mediumInteger('recorder_point')->default(0)->comment('记录人得分');
            $table->mediumInteger('first_approver_point')->default(0)->comment('初审人得分');
            $table->mediumInteger('final_approver_point')->default(0)->comment('终审人分数');
            $table->unsignedSmallInteger('event_count')->default(0)->comment('事件数');
            $table->unsignedSmallInteger('participant_count')->default(0)->comment('事件参与人数');
            
            $table->index('first_approver_sn');
            $table->index('final_approver_sn');
            $table->index('rejecter_sn');
            $table->index('recorder_sn');
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
        Schema::dropIfExists('event_log_groups');
    }
}

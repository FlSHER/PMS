<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFinalApproverTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('final_approvers',function(Blueprint $table){
            $table->increments('id');
            $table->unsignedMediumInteger('staff_sn')->comment('编号');
            $table->char('staff_name',10)->comment('姓名');
            $table->mediumInteger('point_a_awarding_limit')->comment('加A分审核上限');
            $table->mediumInteger('point_a_deducting_limit')->comment('减A分审核上限');
            $table->mediumInteger('point_b_awarding_limit')->comment('加B分审核上限');
            $table->mediumInteger('point_b_deducting_limit')->comment('减B分审核上限');
            $table->index('staff_sn');
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
        Schema::dropIfExists('final_approvers');
    }
}

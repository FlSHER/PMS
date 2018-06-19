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
            $table->mediumInteger('point_a_limit')->comment('A分审核上限');
            $table->mediumInteger('point_b_limit')->comment('B分审核上限');
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

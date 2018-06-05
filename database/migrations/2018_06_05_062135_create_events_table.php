<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->comment('事件名称');
            $table->unsignedTinyInteger('type_id')->nullable()->default(0)->comment('事件类型');
            $table->unsignedInteger('point_a_min')->nullable()->default()->comment('A分最小值');
            $table->unsignedInteger('point_a_max')->nullable()->default()->comment('A分最大值');
            $table->unsignedInteger('point_b_min')->nullable()->default()->comment('B分最小值');
            $table->unsignedInteger('point_b_max')->nullable()->default()->comment('B分最大值');
            $table->unsignedInteger('point_a_default')->nullable()->default()->comment('A分最默认值');
            $table->unsignedInteger('point_b_default')->nullable()->default()->comment('B分最默认值');
            $table->unsignedSmallInteger('is_independent')->nullable()->default(0)->comment('是否专人审核');
            $table->string('first_approver_sn')->nullable()->default('')->comment('初审人编号');
            $table->string('first_approver_name')->nullable()->default('')->comment('初审人姓名');
            $table->string('final_approver_sn')->nullable()->default('')->comment('终审人编号');
            $table->string('final_approver_name')->nullable()->default('')->comment('终审人姓名');
            $table->string('first_approver_locked')->nullable()->default('')->comment('初审人锁定');
            $table->string('final_approver_locked')->nullable()->default('')->comment('终审人锁定');
            $table->string('default_cc_addressees')->nullable()->default('')->comment('默认抄送人');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('events');
    }
}

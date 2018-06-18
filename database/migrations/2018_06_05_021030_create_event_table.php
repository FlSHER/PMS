<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('event_types', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->char('name', 20)->comment('类型名称');
            $table->unsignedSmallInteger('parent_id')->nullable()->comment('上级ID');
            $table->unsignedTinyInteger('sort')->default(99)->comment('排序');
            $table->timestamps();
            $table->softDeletes();
            /* 外键与索引 */
            $table->foreign('parent_id')->references('id')->on('event_types');
        });

        Schema::create('event', function (Blueprint $table) {
            $table->increments('id');
            $table->char('name', 50)->comment('事件名称');
            $table->unsignedSmallInteger('type_id')->comment('事件分类ID');
            $table->mediumInteger('point_a_min')->default(0)->comment('A分最小值');
            $table->mediumInteger('point_a_max')->default(0)->comment('A分最大值');
            $table->mediumInteger('point_b_min')->default(0)->comment('B分最小值');
            $table->mediumInteger('point_b_max')->default(0)->comment('B分最大值');
            $table->mediumInteger('point_a_default')->default(0)->comment('A分默认值');
            $table->mediumInteger('point_b_default')->default(0)->comment('B分默认值');
            $table->unsignedMediumInteger('first_approver_sn')->nullable()->comment('初审人编号');
            $table->char('first_approver_name', 10)->nullable()->comment('初审人姓名');
            $table->unsignedMediumInteger('final_approver_sn')->nullable()->comment('终审人编号');
            $table->char('final_approver_name', 10)->nullable()->comment('终审人姓名');
            $table->tinyInteger('first_approver_locked')->default(0)->comment('初审人锁定 0:否，1:是');
            $table->tinyInteger('final_approver_locked')->default(0)->comment('终审人锁定 0:否，1:是');
            $table->string('default_cc_addressees')->default('')->comment('默认抄送人');
            $table->tinyInteger('is_active')->default(1)->comment('是否激活');
            $table->timestamps();
            $table->softDeletes();
            /* 外键与索引 */
            $table->foreign('type_id')->references('id')->on('event_types');
            $table->index('first_approver_sn');
            $table->index('final_approver_sn');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('event');
        Schema::dropIfExists('event_types');
    }
}

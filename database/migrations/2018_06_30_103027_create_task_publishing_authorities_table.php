<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTaskPublishingAuthoritiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_publishing_authorities', function (Blueprint $table) {
            $table->unsignedInteger('group_id')->comment('分组id');
            $table->unsignedMediumInteger('admin_sn')->comment('管理者编号');
            $table->char('admin_name', 10)->comment('管理者姓名');
            $table->timestamps();
            $table->foreign('group_id')->references('id')->on('authority_groups');
            $table->primary(['group_id', 'admin_sn']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('task_publishing_authorities');
    }
}

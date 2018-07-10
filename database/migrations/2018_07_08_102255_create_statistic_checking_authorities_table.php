<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStatisticCheckingAuthoritiesTable extends Migration
{
    /**
     * Run the migrations.
     *$table->softDeletes();
     * @return void
     */
    public function up()
    {
        Schema::create('statistic_checking_authorities', function (Blueprint $table) {
            $table->unsignedInteger('group_id')->comment('分组id');
            $table->unsignedMediumInteger('admin_sn')->comment('统计查看者编号');
            $table->char('admin_name', 10)->comment('统计查看者姓名');
            $table->timestamps();
            $table->foreign('group_id')->references('id')->on('authority_groups');
            $table->index(['group_id', 'admin_sn']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('statistic_checking_authorities');
    }
}

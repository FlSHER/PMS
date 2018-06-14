<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePersonalPointStatisticsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('personal_point_statistics', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedMediumInteger('staff_sn')->comment('员工编号');
            $table->mediumInteger('point_a')->comment('A分');
            $table->mediumInteger('point_b_monthly')->comment('当月B分');
            $table->mediumInteger('point_b_total')->comment('累计B分');
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
        Schema::dropIfExists('personal_point_statistics');
    }
}

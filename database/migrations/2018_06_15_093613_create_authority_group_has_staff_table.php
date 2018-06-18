<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAuthorityGroupHasStaffTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('authority_group_has_staff', function (Blueprint $table) {
            $table->unsignedInteger('authority_group_id');
            $table->unsignedMediumInteger('staff_sn')->comment('员工编号');
            $table->primary(['authority_group_id', 'staff_sn'], 'authority_group_id_staff_sn');
            $table->char('staff_name',10)->comment('员工姓名');
            $table->foreign('authority_group_id')->references('id')->on('authority_groups');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('authority_group_has_staff');
    }
}

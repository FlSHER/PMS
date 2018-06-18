<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAuthorityGroupHasDepartmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('authority_group_has_departments', function (Blueprint $table) {
            $table->unsignedInteger('authority_group_id');
            $table->unsignedSmallInteger('department_id')->comment('部门id');
            $table->primary(['authority_group_id', 'department_id'], 'authority_group_id_department_id');
            $table->char('department_full_name',100)->comment('部门名字');
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
        Schema::dropIfExists('authority_group_has_departments');
    }
}

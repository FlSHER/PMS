<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCertificateStaffTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('certificate_staff', function (Blueprint $table) {
            $table->integer('staff_sn')->unsigned();
            $table->integer('certificate_id')->unsigned();
            $table->timestamps();

            $table->primary(['staff_sn', 'certificate_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('certificate_staff');
    }
}

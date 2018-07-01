<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePointLogSourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('point_log_sources', function (Blueprint $table) {
            $table->tinyInteger('id')->default(0);
            $table->char('name', 10);

            $table->unique('id');
        });

        Schema::table('point_logs', function (Blueprint $table) {
            $table->foreign('source_id')->references('id')->on('point_log_sources');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('point_logs', function (Blueprint $table) {
            $table->dropForeign('point_logs_source_id_foreign');
            foreign('source_id')->references('id')->on('point_log_sources');
        });

        Schema::dropIfExists('point_log_sources');
    }
}

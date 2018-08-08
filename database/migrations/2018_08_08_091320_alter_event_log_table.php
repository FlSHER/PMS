<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterEventLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('event_logs', function (Blueprint $table) {
            $table->char('withdraw_remark', 255)->nullable()->default('')->comment('撤回备注');
            $table->char('revoke_remark', 255)->nullable()->default('')->comment('撤销备注');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('event_logs', function (Blueprint $table) {
            $table->dropColumn('withdraw_remark');
            $table->dropColumn('revoke_remark');
        });
    }
}

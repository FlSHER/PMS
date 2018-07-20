<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

// 兼容性迁移分类 (迁移运行后删除)
class CreateChangeFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('personal_point_statistic_logs', function (Blueprint $table) {
            $table->mediumInteger('point_a_total')->comment('累计A分');
            $table->text('source_a_monthly')->comment('当月各来源A分');
            $table->text('source_a_total')->comment('累计各来源A分');
        });

        Schema::table('personal_point_statistics', function (Blueprint $table) {
            $table->mediumInteger('point_a_total')->comment('累计A分');
            $table->text('source_a_monthly')->comment('当月各来源A分');
            $table->text('source_a_total')->comment('累计各来源A分');
            $table->renameColumn('calculated_at', 'date');
        });

        Schema::table('event_log_concerns', function (Blueprint $table) {
            $table->unsignedSmallInteger('event_count')->default(0)->comment('事件数');
            $table->unsignedSmallInteger('participant_count')->default(0)->comment('事件参与人数');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('change_fields');
    }
}

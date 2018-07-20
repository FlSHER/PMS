<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePointTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('point_types', function (Blueprint $table) {
            $table->tinyInteger('id')->default(0);
            $table->char('name', 10);
            
            $table->unique('id');
        });

        // 兼容性代码新增字段 (新增后删除)
        Schema::table('point_logs', function (Blueprint $table) {
            $table->unsignedSmallInteger('type_id')->comment('分类ID');
            $table->unsignedSmallInteger('is_revoke')->default(0)->comment('是否撤回记录 0-否 1-是');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('point_types');
    }
}

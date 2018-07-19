<?php

use Illuminate\Database\Seeder;

class PointLogSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::table('point_log_sources')->insert([
            ['id' => 0, 'name' => '系统'],
            ['id' => 1, 'name' => '固定分'],
            ['id' => 2, 'name' => '奖扣'],
            ['id' => 3, 'name' => '任务'],
            ['id' => 4, 'name' => '考勤'],
            ['id' => 5, 'name' => '日志'],
        ]);
    }
}

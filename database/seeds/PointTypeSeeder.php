<?php

use Illuminate\Database\Seeder;

class PointTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::table('point_types')->insert([
            ['id' => 0, 'name' => '基础'],
            ['id' => 1, 'name' => '工作'],
            ['id' => 2, 'name' => '行政'],
            ['id' => 3, 'name' => '创新'],
            ['id' => 4, 'name' => '其他'],
        ]);
    }
}

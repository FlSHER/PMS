<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Console\Command;
use App\Models\ArtisanCommandLog;
use App\Models\CommonConfig;
use App\Models\AuthorityGroupHasStaff;
use App\Services\Point\Types\BasePoint;


class CalculateStaffBasePoint extends Command
{
	protected $signature = 'pms:calculate-staff-basepoint';
    protected $description = 'Calculate staff base point';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
    	// 基础分配置项
        $configs = CommonConfig::byNamespace('basepoint')->get();
        // 基础工龄系数
        $ratio = CommonConfig::byNamespace('basepoint')->byName('ratio')->pluck('value');
        // 所有权限分组员工
        $staff_sns = AuthorityGroupHasStaff::pluck('staff_sn')->unique()->values()->toJson();
        $users = app('api')->getStaff(['filters' => 'staff_sn='.$staff_sns]);

        $commandModel = $this->createLog();

        try {
            \DB::beginTransaction();
            
            foreach ($users as $key => &$val) {
                $val['base_point'] = 0;
                
                $configs->map(function ($config) use (&$val, $ratio) {

                    // json 转数组
                    $toArray = json_decode($config['value'], true);

                    // 匹配学历基础分
                    if ($config['name'] == 'education') {
                        $match = array_first($toArray, function ($item, $key) use ($val) {
                            return $item['name'] == $val['education'];
                        });
                        $val['base_point'] += $match['point'];
                    }

                    // 匹配职位基础分
                    if ($config['name'] == 'position') {
                        $match1 = array_first($toArray, function ($item, $key) use ($val) {
                            return $item['id'] == $val['position']['id'];
                        });
                        $val['base_point'] += $match1['point'];
                    }

                    // 计算工龄基础分
                    if ($config['name'] == 'max_point') {
                        
                        // 员工工龄转月数
                        $month = Carbon::parse($val['employed_at'])->diffInMonths(Carbon::now());
                        $point = ceil($month * $ratio[0]);
                        $val['base_point'] += ($point >= $config['value']) ? $config['value'] : $point;
                    }
                });

                app(BasePoint::class)->record($val);
            }

            $commandModel->status = 1;
            $commandModel->save();

            \DB::commit();
        } catch (Exception $e) {
            $commandModel->status = 2;
            $commandModel->save();

            \DB::rollBack();
        }

    }
	

    /**
     * 创建积分日志.
     * 
     * @author 28youth
     * @return ArtisanCommandLog
     */
	public function createLog(): ArtisanCommandLog
	{
		$commandModel = new ArtisanCommandLog();
        $commandModel->command_sn = 'pms:calculate-staff-basepoint';
        $commandModel->created_at = Carbon::now();
        $commandModel->title = '每月基础分结算';
        $commandModel->status = 0;
        $commandModel->save();

        return $commandModel;
	}
}
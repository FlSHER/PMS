<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Jobs\BasePoint;
use App\Models\CommonConfig;
use Illuminate\Http\Request;
use Illuminate\Console\Command;
use App\Models\CertificateStaff;
use App\Models\ArtisanCommandLog;
use App\Models\PointLog as PointLogModel;
use App\Models\AuthorityGroupHasStaff as GroupStaff;
use App\Models\AuthorityGroupHasDepartment as GroupDepartment;

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
        $ratio = CommonConfig::byNamespace('basepoint')->byName('ratio')->value('value');
        // 所有权限分组员工
        $staff_sns = GroupStaff::pluck('staff_sn')->unique()->values();
        // $department = GroupDepartment::pluck('department_id')->values();
        $users = app('api')->client()->getStaff(['filters' => "staff_sn={$staff_sns};status_id>=0"]);
        $commandModel = $this->createLog();

        $data = [];$logs = [];
        foreach ($users as $key => &$val) {
            $val['base_point'] = 0;

            $configs->map(function ($config) use (&$val, $ratio, $key, &$logs) {

                $toArray = json_decode($config['value'], true);

                // 匹配学历基础分
                /*if ($config['name'] == 'education') {
                    $match = array_first($toArray, function ($item, $key) use ($val) {
                        return $item['name'] == $val['education'];
                    });
                    $val['base_point'] += $match['point'];

                    if (!isset($logs['education'])) {
                        $logs['education'] = ['title' => '学历基础分结算', 'type' => 'education', 'point' => $match['point']];
                    }
                }*/

                // 匹配职位基础分
                if ($config['name'] == 'position') {
                    $match = array_first($toArray, function ($item, $key) use ($val) {
                        return $item['id'] == $val['position']['id'];
                    });
                    $val['base_point'] += $match['point'];

                    if (!isset($logs['position'])) {
                        $logs['position'] = ['title' => '职位基础分结算', 'type' => 'position', 'point' => $match['point']];
                    }
                }

                // 计算工龄基础分
                if ($config['name'] == 'max_point') {
                    // 员工工龄转月数
                    $month = Carbon::parse($val['employed_at'])->diffInMonths(Carbon::now());
                    $point = ceil($month * $ratio);
                    $point = ($point >= $config['value']) ? $config['value'] : $point;
                    $val['base_point'] += $point;

                    if (!isset($logs['max_point'])) {
                        $logs['max_point'] = ['title' => '工龄基础分结算', 'type' => 'max_point', 'point' => $point];
                    }
                }
            });

            // 计算证书得分
            $total = CertificateStaff::query()
                ->where('staff_sn', $val['staff_sn'])
                ->select(\DB::raw('SUM(certificates.point) as total'))
                ->leftJoin('certificates', 'certificate_staff.certificate_id', '=', 'certificates.id')
                ->value('total');
            if ($total !== null) {
                $val['base_point'] += $total;

                if (!isset($logs['certificate'])) {
                    $logs['certificate'] = ['title' => '证书基础分结算', 'type' => 'certificate', 'point' => $total];
                }
            }

            if ($val['base_point']) {
                $data[$key] = [
                    'title' => now()->month . '月基础分统计',
                    'staff_sn' => $val['staff_sn'],
                    'staff_name' => $val['realname'],
                    'brand_id' => $val['brand']['id'],
                    'brand_name' => $val['brand']['name'],
                    'department_id' => $val['department_id'],
                    'department_name' => $val['department']['full_name'],
                    'shop_sn' => $val['shop_sn'],
                    'shop_name' => $val['shop']['name'],
                    'point_b' => $val['base_point'],
                    'changed_at' => now()->startOfMonth(),
                    'source_id' => 1,
                    'type_id' => 0
                ];
            }
        }
        try {
            \DB::beginTransaction();

            foreach ($data as $key => $val) {
                $model = new PointLogModel();
                $model->fill($val);
                $model->save();

                $logData = collect($logs)->map(function ($item) use ($model) {
                    $item['source_foreign_key'] = $model->id;
                    $item['staff_sn'] = $model->staff_sn;
                    $item['staff_name'] = $model->staff_name;
                    $item['created_at'] = now();

                    return $item;
                })->toArray();
                \DB::table('point_calculate_logs')->insert($logData);
            }
           /* if (!empty($data)) {
                \DB::table('point_logs')->insert($data);
            }*/

            $commandModel->save();

            \DB::commit();
        } catch (Exception $e) {
            $commandModel->status = 2;
            $commandModel->save();

            \DB::rollBack();
        }

    }


    /**
     * 基础分记录.
     * 
     * @author 28youth
     * @param  array
     * @return mixed
     */
    public function createPointLog($staff)
    {
        $model = new PointLogModel();

        $model->title = now()->month . '月基础分统计';
        $model->staff_sn = $staff['staff_sn'];
        $model->staff_name = $staff['realname'];
        $model->brand_id = $staff['brand']['id'];
        $model->brand_name = $staff['brand']['name'];
        $model->department_id = $staff['department_id'];
        $model->department_name = $staff['department']['full_name'];
        $model->shop_sn = $staff['shop_sn'];
        $model->shop_name = $staff['shop']['name'];
        $model->point_b = $staff['base_point'];
        $model->changed_at = now()->startOfMonth();
        $model->source_id = 1;
        $model->type_id = 0;
        $model->save();
    }

    /**
     * 创建积分日志.
     * 
     * @author 28youth
     * @return ArtisanCommandLog
     */
    public function createLog() : ArtisanCommandLog
    {
        $commandModel = new ArtisanCommandLog();
        $commandModel->command_sn = 'pms:calculate-staff-basepoint';
        $commandModel->created_at = now();
        $commandModel->title = now()->month . '月基础分结算';
        $commandModel->status = 1;

        return $commandModel;
    }
}
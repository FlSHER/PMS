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
use App\Models\BasePointLog as BasePointLogModel;
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
                    $data = ['职位' => $match['name']];

                    if (!isset($logs['position'])) {
                        $logs['position'] = [
                            'title' => '职位基础分结算',
                            'type' => 'position',
                            'point' => $match['point'],
                            'data' => json_encode($data)
                        ];
                    }
                }

                // 计算工龄基础分
                if ($config['name'] == 'max_point') {
                    // 员工工龄转年数
                    $year = Carbon::parse($val['employed_at'])->diffInYears(now());
                    if ($year <= 0) {
                        return false;
                    }
                    $point = ceil($year * $ratio);
                    $point = ($point >= $config['value']) ? $config['value'] : $point;
                    $val['base_point'] += $point;
                    $data = [
                        '入职时间' => $val['employed_at'],
                        '工龄' => $year,
                    ];

                    if (!isset($logs['max_point'])) {
                        $logs['max_point'] = [
                            'title' => '工龄基础分结算',
                            'type' => 'max_point',
                            'point' => $point,
                            'data' => json_encode($data)
                        ];
                    }
                }
            });

            // 计算证书得分
            $certificates = CertificateStaff::query()
                ->where('staff_sn', $val['staff_sn'])
                ->leftJoin('certificates', 'certificate_staff.certificate_id', '=', 'certificates.id')
                ->get();
            if ($certificates !== null) {
                $total = $certificates->reduce(function ($carry, $item) {
                    return $carry + $item['point'];
                });
                $val['base_point'] += $total;

                if (!isset($logs['certificate'])) {
                    $logs['certificate'] = [
                        'title' => '证书基础分结算',
                        'type' => 'certificate',
                        'point' => $total,
                        'data' => $certificates->toJson()
                    ];
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
                // 记录基础分记录
                $baseModel = new BasePointLogModel();
                $baseModel->fill($val);
                $baseModel->save();

                // 基础分转总积分记录
                $logModel = new PointLogModel();
                $logModel->fill($val);
                $logModel->source_foreign_key = $baseModel->id;
                $logModel->save();

                // 记录基础分详细记录
                \DB::table('base_point_details')->insert(array_map(function($item) use ($baseModel) {
                    $item['source_foreign_key'] = $baseModel->id;
                    $item['staff_sn'] = $baseModel->staff_sn;
                    $item['staff_name'] = $baseModel->staff_name;
                    $item['created_at'] = now();
                    return $item;
                }, $logs));
            }

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

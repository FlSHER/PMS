<?php

namespace App\Console\Commands;

use App\Models\BasePointLog;
use App\Models\CommonConfig;
use Illuminate\Console\Command;
use App\Models\CertificateStaff;
use App\Models\ArtisanCommandLog;
use App\Models\PointLog as PointLogModel;
use App\Models\AuthorityGroupHasStaff as GroupStaff;
use App\Models\AuthorityGroupHasDepartment as GroupDepartment;

class ConvertOneTimePoint extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pms:one-time-point-convert';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '一次性积分转化';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $staff_sns = GroupStaff::pluck('staff_sn')->unique()->values();
        $department = GroupDepartment::pluck('department_id')->values();
        $users = app('api')->client()->getStaff([
            'filters' => "(staff_sn={$staff_sns})|(department_id={$department});status_id>=0"
        ]);
        if (empty($users)) {
            return false;
        }

        $excloud = BasePointLog::where('type', 'baseEdu')->pluck('staff_sn')->toArray();
        $config = CommonConfig::byNamespace('basepoint')->byName('education')->first();
        $toConfig = json_decode($config['value'], true);

        $commandModel = $this->createLog();
        try {
            \DB::beginTransaction();

            foreach ($users as $key => $user) {
                // 排除已结算
                if (!in_array($user['staff_sn'], $excloud)) {
                    $match = array_first($toConfig, function ($item) use ($user) {
                        return $item['name'] == $user['education'];
                    });

                    if (!empty($match)) {
                        $this->createPointLog($user, $match['point']);

                        $this->createBaseEduLog($user, $match['point']);
                    }
                }
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
     * 记录考勤分结算.
     *
     * @author 28youth
     * @param  array $user
     * @param  integer $point
     */
    public function createPointLog($user, $point)
    {
        $model = new PointLogModel();
        $model->title = '学历分结算';
        $model->staff_sn = $user['staff_sn'];
        $model->staff_name = $user['realname'];
        $model->brand_id = $user['brand']['id'];
        $model->brand_name = $user['brand']['name'];
        $model->department_id = $user['department_id'];
        $model->department_name = $user['department']['full_name'];
        $model->shop_sn = $user['shop_sn'];
        $model->shop_name = $user['shop']['name'];
        $model->changed_at = null;
        $model->point_b = $point;
        $model->source_id = 3;
        $model->type_id = 0;
        $model->save();
    }

    /**
     * 学历分结算记录.
     *
     * @author 28youth
     * @param  array $user
     * @param  integer $point
     */
    public function createBaseEduLog($user, $point)
    {
        $model = new BasePointLog();
        $model->title = '学历分结算';
        $model->staff_sn = $user['staff_sn'];
        $model->staff_name = $user['realname'];
        $model->brand_id = $user['brand']['id'];
        $model->brand_name = $user['brand']['name'];
        $model->department_id = $user['department_id'];
        $model->department_name = $user['department']['full_name'];
        $model->shop_sn = $user['shop_sn'];
        $model->shop_name = $user['shop']['name'];
        $model->point_b = $point;
        $model->type = 'baseEdu';
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
        $commandModel->command_sn = 'pms:one-time-point-convert';
        $commandModel->created_at = now();
        $commandModel->title = now()->month . '月学历分结算';
        $commandModel->status = 1;

        return $commandModel;
    }
}

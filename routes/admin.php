<?php

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| 后台功能路由
|
*/

use App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Route;
use Illuminate\Contracts\Routing\Registrar as RouteContract;

Route::options('{a?}/{b?}/{c?}', function () {
    return response('', 204);
});
Route::group(['middleware' => 'auth:api'], function (RouteContract $admin) {
    //事件表接口
    $admin->get('/events', Admin\EventController::class . '@index');//事件列表ok
    $admin->post('/events', Admin\EventController::class . '@store');//添加事件 ok
    $admin->put('events/{id}', Admin\EventController::class . '@update');//编辑事件ok
    $admin->delete('events/{id}', Admin\EventController::class . '@delete');//删除事件ok
    $admin->post('events/import', Admin\EventController::class . '@import');//导入事件ok
    $admin->get('events/export', Admin\EventController::class . '@export');//导出事件ok
    //事件分类表接口
    $admin->get('/events/types', Admin\EventController::class . '@indexType');//事件分类列表ok
    $admin->patch('/events/types', Admin\EventController::class . '@refactorType');//事件分类排序，修改父级ok
    $admin->post('/events/types', Admin\EventController::class . '@storeType');//事件分类添加ok
    $admin->put('/events/types/{id}', Admin\EventController::class . '@updateType');//编辑事件分类ok
    $admin->delete('/events/types/{id}', Admin\EventController::class . '@deleteType');//删除事件分类ok
    $admin->post('events/{eventlog}/revoke', Admin\EventController::class . '@revoke'); //撤销事件操作

    //终审人接口
    $admin->get('/finals', Admin\FinalsController::class . '@index');//终审人列表
    $admin->post('/finals', Admin\FinalsController::class . '@store');//添加终审人
    $admin->put('/finals/{id}', Admin\FinalsController::class . '@edit');//编辑终审人
    $admin->delete('/finals/{id}', Admin\FinalsController::class . '@delete');//终审人删除
    //权限分组
    $admin->get('auth/groups', Admin\AuthorityController::class . '@indexGroup');//获取分组列表 ok
    $admin->post('auth/groups', Admin\AuthorityController::class . '@storeGroup');//添加权限分组 ok
    $admin->put('auth/groups/{id}', Admin\AuthorityController::class . '@editGroup');//编辑权限分组
    $admin->delete('auth/groups/{id}', Admin\AuthorityController::class . '@deleteGroup');//删除分组
    //积分变动日志
    $admin->get('point-log', Admin\PointController::class . '@index');//积分变动列表
    $admin->get('point-log/{id}', Admin\PointController::class . '@details');//积分变动详情页面
    $admin->get('point/export', Admin\PointController::class . '@export');//积分变动导出 暂时不用
    //奖扣任务
    $admin->get('targets', Admin\PointTargetController::class . '@targets');//获取奖扣指标列表
    $admin->get('targets/{id}', Admin\PointTargetController::class . '@targetsDetails');//获取奖扣指标详情
    $admin->post('targets', Admin\PointTargetController::class . '@storeTarget');//添加奖扣指标
    $admin->put('targets/{id}', Admin\PointTargetController::class . '@editTarget');//修改奖扣指标
    $admin->put('targets/{id}/staff', Admin\PointTargetController::class . '@editStaff');//修改奖扣指标关联人员
    $admin->delete('targets/{id}', Admin\PointTargetController::class . '@deleteTarget');//删除奖扣指标
    $admin->get('target', Admin\PointTargetController::class . '@test');//测试    PointTargetCommand
    //任务分配权限
    $admin->get('task/authority', Admin\TaskAuthorityController::class . '@index');//任务分配list页面
    $admin->post('task/authority', Admin\TaskAuthorityController::class . '@store');//增加
    $admin->put('task/authority', Admin\TaskAuthorityController::class . '@edit');//编辑
    $admin->delete('task/authority/{admin_sn}', Admin\TaskAuthorityController::class . '@delete');//删除
    //统计查看权限
    $admin->get('statistic', Admin\StatisticController::class . '@index');//获取统计权限列表
    $admin->post('statistic', Admin\StatisticController::class . '@store');//添加统计权限
    $admin->put('statistic', Admin\StatisticController::class . '@edit');//编辑统计权限
    $admin->delete('statistic/{admin_sn}', Admin\StatisticController::class . '@delete');//删除统计权限
    // 获取基础分配置
    // @get /admin/base-points/setting
    $admin->get('base-points/setting', Admin\BasePointController::class . '@index');

    // 存储基础分配置
    // @patch /admin/base-points/setting
    $admin->patch('base-points/setting', Admin\BasePointController::class . '@store');

    // 存储单个配置
    // @patch /admin/base-points/setting
    $admin->post('base-points/setting', Admin\BasePointController::class . '@storeSeniority');

    // route 证书配置
    $admin->group(['prefix' => 'certificates'], function (RouteContract $admin) {

        // 获取全部证书
        // @get /admin/certificates
        $admin->get('/', Admin\CertificateController::class . '@index');

        // 添加一个证书
        // @post /admin/certificates
        $admin->post('/', Admin\CertificateController::class . '@store');

        // 修改一个证书
        // @put /admin/certificates/:certificate
        $admin->put('{certificate}', Admin\CertificateController::class . '@update');

        // 删除一个证书
        // @delete /admin/certificates/:certificate
        $admin->delete('{certificate}', Admin\CertificateController::class . '@delete');
    });

    // 获取全部证书拥有者
    // @get /admin/certificate-staff
    $admin->get('certificate-staff', Admin\CertificateController::class . '@getCertificateStaff');

    // 批量分配证书
    // @put /admin/certificate-staff/batch/add
    $admin->put('certificate-staff/batch/add', Admin\CertificateController::class . '@storeCertificateStaff');

    // 批量删除证书拥有者
    // @post /admin/certificate-staff/batch/delete
    $admin->post('certificate-staff/batch/delete', Admin\CertificateController::class . '@deleteCertificateStaff');

    // 获取任务执行记录
    // @get /admin/commadn-logs
    $admin->get('commadn-logs', Admin\CommandLogController::class . '@index');
});

Route::get('events/example', Admin\EventController::class . '@example');//导出模板范例ok  record
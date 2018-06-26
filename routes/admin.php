<?php

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| 后台功能路由
|
*/

use Illuminate\Support\Facades\Route;
use Illuminate\Contracts\Routing\Registrar as RouteContract;
use App\Http\Controllers\Admin;

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
    $admin->get('events/example', Admin\EventController::class . '@example');//导出模板范例ok
    //事件分类表接口
    $admin->get('/events/types', Admin\EventController::class . '@indexType');//事件分类列表ok
    $admin->patch('/events/types', Admin\EventController::class . '@refactorType');//事件分类排序，修改父级ok
    $admin->post('/events/types', Admin\EventController::class . '@storeType');//事件分类添加ok
    $admin->put('/events/types/{id}', Admin\EventController::class . '@updateType');//编辑事件分类ok
    $admin->delete('/events/types/{id}', Admin\EventController::class . '@deleteType');//删除事件分类ok
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

    // 获取基础分配置
    // @get /admin/base-points/setting
    $admin->get('base-points/setting', Admin\BasePointController::class . '@index');

    // 存储基础分配置
    // @patch /admin/base-points/setting
    $admin->patch('base-points/setting', Admin\BasePointController::class . '@store');

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
    $admin->post('certificate-staff/batch/delete',Admin\CertificateController::class . '@deleteCertificateStaff');

    // 获取任务执行记录
    // @get /admin/commadn-logs
    $admin->get('commadn-logs', Admin\CommandController::class.'@index');
});

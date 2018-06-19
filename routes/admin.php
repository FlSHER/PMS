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
    $admin->get('/events', Admin\EventController::class.'@index');//事件列表ok
    $admin->post('/events',Admin\EventController::class.'@store');//添加事件 ok
    $admin->put('events/{id}',Admin\EventController::class.'@update');//编辑事件ok
    $admin->delete('events/{id}',Admin\EventController::class.'@delete');//删除事件ok
    $admin->post('events/import',Admin\EventController::class.'@import');//导入事件ok
    $admin->get('events/export',Admin\EventController::class.'@export');//导出事件ok
    $admin->get('events/example',Admin\EventController::class.'@example');//导出模板范例ok

    //事件分类表接口
    $admin->get('/events/types',Admin\EventController::class.'@indexType');//事件分类列表ok
    $admin->patch('/events/types',Admin\EventController::class.'@refactorType');//事件分类排序，修改父级ok
    $admin->post('/events/types',Admin\EventController::class.'@storeType');//事件分类添加ok
    $admin->put('/events/types/{id}',Admin\EventController::class.'@updateType');//编辑事件分类ok
    $admin->delete('/events/types/{id}',Admin\EventController::class.'@deleteType');//删除事件分类ok

    //终审人接口
//    $admin->get('/finals',Admin\AuthorityController::class);//终审人列表  //todo 终审人查重复

    //权限分组
    $admin->get('auth/groups',Admin\AuthorityController::class.'@indexGroup');//获取分组列表 ok
    $admin->post('auth/groups',Admin\AuthorityController::class.'@storeGroup');//添加权限分组 ok
    $admin->put('auth/groups/{id}',Admin\AuthorityController::class.'@editGroup');//编辑权限分组
    $admin->delete('auth/groups/{id}',Admin\AuthorityController::class.'@deleteGroup');//删除分组

    // 获取基础分配置
    // @get /admin/base-points/setting
    $admin->get('base-points/setting', Admin\BasePointController::class.'@index');
    
    // 存储基础分配置
    // @patch /admin/base-points/setting
    $admin->patch('base-points/setting', Admin\BasePointController::class.'@store');

});

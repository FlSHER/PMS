<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Contracts\Routing\Registrar as RouteContract;
use App\Http\Controllers\APIs;

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => 'auth:api'], function (RouteContract $api) {

    // @route 事件奖扣
    $api->group(['prefix' => 'event-logs'], function (RouteContract $api) {

        // 事件日志列表
        // @get /api/event-logs
        $api->get('/', APIs\EventLogController::class.'@index');

        // 事件类型列表
        // @get /api/event-logs/categories
        $api->get('/categories', APIs\EventLogController::class.'@cates');

        // 事件列表
        // @get /api/event-logs/:category/events
        $api->get('{category}/events', APIs\EventLogController::class.'@events');

        // 事件列表
        // @get /api/event-logs/final-staff
        $api->get('final-staff', APIs\EventLogController::class.'@finalStaff');

        // 添加事件日志
        // @post /api/event-logs/event
        $api->post('event', APIs\EventLogController::class.'@store');

        // 奖扣事件详情
        // @get /api/event-logs/:eventlog
        $api->get('{eventlog}', APIs\EventLogController::class.'@show');

        // 初审奖扣事件
        // @put /api/event-logs/:eventlog/first-approve
        $api->put('{eventlog}/first-approve', APIs\EventLogController::class.'@firstApprove');

        // 终审奖扣事件
        // @put /api/event-logs/:eventlog/final-approve
        $api->put('{eventlog}/final-approve', APIs\EventLogController::class.'@finalApprove');

        // 驳回奖扣事件
        // @put /api/event-logs/:eventlog/reject
        $api->put('{eventlog}/reject', APIs\EventLogController::class.'@reject');

        // 撤回奖扣事件
        // @put /api/event-logs/:eventlog/withdraw
        $api->put('{eventlog}/withdraw', APIs\EventLogController::class.'@withdraw');
    });

    // route 积分制
    $api->group(['prefix' => 'points'], function (RouteContract $api) {

        // 积分排行详情
        // get /api/points/ranking/show
        $api->get('ranking/show', APIs\PointRankController::class.'@show');

        // 员工分组排行榜
        // get /api/points/ranking/staff
        $api->get('ranking/staff', APIs\PointRankController::class.'@staff');

        // 我的积分首页
        // get /api/points/statistic/mine
        $api->get('statistic/mine', APIs\StaffPointController::class.'@index');

        // 积分列表
        // get /api/points/statistic/log
        $api->get('statistic/log', APIs\StaffPointController::class.'@show');

        // 积分详情
        // get /api/points/statistic/:pointlog
        $api->get('statistic/{pointlog}', APIs\StaffPointController::class.'@detail');
    });

    // route 员工相关
    $api->group(['prefix' => 'staff'], function (RouteContract $api) {

        // 获取员工权限分组
        // get /api/staff/authority-groups
        $api->get('authority-groups', APIs\AuthorityController::class.'@index');
    });

});

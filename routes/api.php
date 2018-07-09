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
use App\Http\Controllers\APIs;
use Illuminate\Support\Facades\Route;
use Illuminate\Contracts\Routing\Registrar as RouteContract;

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => 'auth:api'], function (RouteContract $api) {

    // @route 事件奖扣
    $api->group(['prefix' => 'event'], function (RouteContract $api) {

        // 事件列表
        // @get /api/event
        $api->get('/', APIs\EventController::class . '@index');

        // 事件类型列表
        // @get /api/event/categories
        $api->get('categories', APIs\EventController::class . '@cates');

        // 分类事件列表
        // @get /api/event/:category/events
        $api->get('{category}/events', APIs\EventController::class . '@events');

        // 事件终审人列表
        // @get /api/event/final-staff
        $api->get('final-staff', APIs\EventController::class . '@finalStaff');

    });

    // @route 事件奖扣
    $api->group(['prefix' => 'event-logs'], function (RouteContract $api) {

        // 事件日志列表
        // @get /api/event-logs
        $api->get('/', APIs\EventLogController::class . '@index');        

        // 添加事件日志
        // @post /api/event-logs/event
        $api->post('event', APIs\EventLogController::class . '@store');

        // 奖扣事件详情
        // @get /api/event-logs/:eventlog
        $api->get('{eventlog}', APIs\EventLogController::class . '@show');

        // 初审奖扣事件
        // @put /api/event-logs/:eventlog/first-approve
        $api->put('{eventlog}/first-approve', APIs\EventLogController::class . '@firstApprove');

        // 终审奖扣事件
        // @put /api/event-logs/:eventlog/final-approve
        $api->put('{eventlog}/final-approve', APIs\EventLogController::class . '@finalApprove');

        // 驳回奖扣事件
        // @put /api/event-logs/:eventlog/reject
        $api->put('{eventlog}/reject', APIs\EventLogController::class . '@reject');

        // 撤回奖扣事件
        // @put /api/event-logs/:eventlog/withdraw
        $api->put('{eventlog}/withdraw', APIs\EventLogController::class . '@withdraw');
    });

    // route 积分制
    $api->group(['prefix' => 'points'], function (RouteContract $api) {

        // 积分排行详情
        // get /api/points/ranking/show
        $api->get('ranking/show', APIs\PointRankController::class . '@show');

        // 认证员工分组排行榜
        // get /api/points/ranking/staff
        $api->get('ranking/staff', APIs\PointRankController::class . '@staff');
        
        // 查看员工统计排行
        // get /api/points/statistic/ranking
        $api->get('statistic/ranking', APIs\StatisticController::class . '@staff');
        
        // 我的积分首页
        // get /api/points/statistic/mine
        $api->get('statistic/mine', APIs\StaffPointController::class . '@index');

        // 积分列表
        // get /api/points/statistic/log
        $api->get('statistic/log', APIs\StaffPointController::class . '@show');

        // 积分详情
        // get /api/points/statistic/:pointlog
        $api->get('statistic/{pointlog}', APIs\StaffPointController::class . '@detail');
    });


    // 当前员工积分指标
    // @get /api/staff/target
    $api->get('user/target', APIs\TargetController::class . '@index');

    // 获取员工积分排名权限分组
    // get /api/authority-group/rank
    $api->get('authority-group/rank', APIs\AuthorityController::class . '@index');

});

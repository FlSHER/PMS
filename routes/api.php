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

        // 添加事件日志
        // @post /api/event-logs/:event/event
        $api->post('{event}/event', APIs\EventLogController::class.'@store');

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

        $api->get('ranking/show', APIs\PointRankController::class.'@show');

        $api->get('ranking/staff', APIs\PointRankController::class.'@staff');
    });

    // route 员工相关
    $api->group(['prefix' => 'staff'], function (RouteContract $api) {

        // 获取员工权限分组
        $api->get('authority-groups', APIs\AuthorityController::class.'@index');
    });

});

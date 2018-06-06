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

		// 初审奖扣事件
		// @post /api/event-logs/:eventlog/first-approve
		$api->post('{eventlog}/first-approve', APIs\EventLogController::class.'@firstApprove');

		// 终审奖扣事件
		// @post /api/event-logs/:eventlog/final-approve
		$api->post('{eventlog}/final-approve', APIs\EventLogController::class.'@finalApprove');

	// @post /api/event-logs/:eventlog/final-approve
		// 驳回奖扣事件
		// @post /api/event-logs/:eventlog/reject
		$api->post('{eventlog}/reject', APIs\EventLogController::class.'@reject');
	});

});

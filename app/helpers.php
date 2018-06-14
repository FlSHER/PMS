<?php 

namespace App;

use Carbon\Carbon;

/**
 * 获取本月开始到结束时间.
 * 
 * @author 28youth
 * @return array
 */
function curMonthBetween()
{
	$time = Carbon::now();
    $stime = Carbon::create(null, null, 01);
    $etime = Carbon::create(null, null, $time->daysInMonth);

    return [$stime, $etime];
}
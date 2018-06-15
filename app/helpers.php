<?php 

namespace App;

use Carbon\Carbon;

/**
 * 获取某月开始结束.
 * 
 * @author 28youth
 * @return array
 */
function monthBetween($datetime = ''): array
{
	$time = $datetime ? Carbon::parse($datetime) : Carbon::now();
    $stime = Carbon::create($time->year, $time->month, 01);
	$etime = Carbon::create($time->year, $time->month, $time->daysInMonth);

    return [$stime, $etime];
}
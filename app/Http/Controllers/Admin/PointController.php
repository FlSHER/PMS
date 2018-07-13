<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Services\Admin\PointService;

class PointController extends Controller
{
    protected $point;

    public function __construct(PointService $point)
    {
        $this->point = $point;
    }

    /**
     * @param Request $request
     * 积分变动日志
     */
    public function index(Request $request)
    {
        return $this->point->index($request);
    }

    /**
     * @param Request $request
     * @return mixed
     *积分变动详情
     */
    public function details(Request $request)
    {
        return $this->point->getDetails($request);
    }

    /**
     * @param Request $request .
     * 积分变动导出
     */
    public function export(Request $request)
    {
        return $this->point->export($request);
    }
}
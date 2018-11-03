<?php

namespace App\Repositories;

use App\Models\PointLog;
use Illuminate\Http\Request;

use Illuminate\Database\Eloquent\Model;

class PointRepository
{
    protected $point;

    public function __construct(PointLog $pointLog)
    {
        $this->point = $pointLog;
    }

    /**
     * @param $request
     * @return array
     * 带分页
     */
    public function getPointList($request)
    {
        return $this->point
            ->filterByQueryString()
            ->sortByQueryString()
            ->withPagination($request->get('pagesize', 10));
    }

    /**
     * @param $request
     * @return mixed
     * 获取单条信息
     */
    public function getDetailsData($request)
    {
        $id = $request->route('id');
        return $this->point->find($id);
    }

    /**
     * 接口增加数据
     *
     * @param $all
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function storePointData($all)
    {
        if (count($all) == count($all, 1)) {
            $all['created_at'] = date('Y-m-d H:i:s');
            $all['updated_at'] = date('Y-m-d H:i:s');
            return response($this->point->create($all), 201);
        } else {
            $arr = [];
            foreach ($all as $key => $value) {
                $value['created_at'] = date('Y-m-d H:i:s');
                $value['updated_at'] = date('Y-m-d H:i:s');
                $arr[] = $this->point->create($value);
            }
            return response($arr, 201);
        }
    }

    /**
     * @param $id
     */
    public function delPoint($id)
    {
        $data = $this->point->find($id);
        if($data == false){
            return response('未找到数据',404);
        }
        $data->delete();
        return response('',204);
    }

    /**
     * @param $request
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     * 无分页
     */
    public function getPointData($request)
    {
        $builder = ($this->point instanceof Model) ? $this->point->query() : $this->point;
        $sort = explode('-', $request->sort);
        $filters = $request->query('filters', '');
        if ($filters && $filters !== null) {
            $maps = $this->formatFilter($filters);
            foreach ($maps['maps'] as $k => $map) {
                $curKey = $maps['fields'][$k];
                $builder->when($curKey, $map[$curKey]);
            }
        }
        $builder->when(($sort && !$sort), function ($query) use ($sort) {
            $query->orderBy($sort[0], $sort[1]);
        });
        $items = $builder->get();
        return $items;
    }
}
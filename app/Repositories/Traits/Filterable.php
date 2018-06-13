<?php

namespace App\Repositories\Traits;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

Trait Filterable
{

    /**
     * 获取筛选列表(带分页).
     *
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    protected function getFilteredPaginateList(Request $request, $model)
    {
        $builder = ($model instanceof Model) ? $model->query() : $model;
        $sort = explode('-', $request->sort);
        $limit = $request->query('limit', 20);
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
        $items = $builder->paginate($limit);

        return [
            'data' => $items->items(),
            'total' => $items->count(),
            'page' => $items->currentPage(),
            'pagesize' => $limit,
            'totalpage' => $items->total(),
        ];
    }

    /**
     * 获取筛选列表.
     *
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    protected function getFilteredList(Request $request, $model)
    {
        $builder = ($model instanceof Model) ? $model->query() : $model;
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

    /**
     * filter 格式化.
     *
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function formatFilter(string $filters): array
    {
        $maps = $fields = [];
        $filters = array_filter(explode(';', $filters));
        foreach ($filters as $key => $value) {
            preg_match('/(=|~|>=|>|<=|<)/', $value, $match);
            $filter = explode($match[0], $value);
            switch ($match[0]) {
                case '=':
                    if (strpos($filter[1], '[', 0) !== false) {
                        $toArr = explode(',', trim($filter[1], '[]'));
                        array_push($maps, [
                            $filter[0] => function ($query) use ($filter, $toArr) {
                                $query->whereIn($filter[0], $toArr);
                            }
                        ]);
                        continue;
                    }
                    array_push($maps, [
                        $filter[0] => function ($query) use ($filter) {
                            $query->where($filter[0], $filter[1]);
                        }
                    ]);
                    break;

                case '~':
                    array_push($maps, [
                        $filter[0] => function ($query) use ($filter) {
                            $query->where($filter[0], 'like', "%{$filter[1]}%");
                        }
                    ]);
                    break;

                default:
                    array_push($maps, [
                        $filter[0] => function ($query) use ($filter, $match) {
                            $query->where($filter[0], $match[0], $filter[1]);
                        }
                    ]);
                    break;
            }

            array_push($fields, $filter[0]);
        }

        return [
            'maps' => $maps,
            'fields' => $fields,
        ];
    }

}
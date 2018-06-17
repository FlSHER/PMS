<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

Trait ListScopes
{
    /**
     * 格式化 filter 参数，转换成sql
     *
     * @author 28youth
     * @return  \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterByQueryString(Builder $query): Builder
    {
        $filters = array_filter(explode(';', request('filters', '')));
        return $query->when($filters, function ($query) use ($filters) {
            foreach ($filters as $key => $value) {
                preg_match('/(?<mark>=|~|>=|>|<=|<)/', $value, $match);
                $filter = explode($match['mark'], $value);
                switch ($match['mark']) {
                    case '=':
                        if (strpos($filter[1], '[', 0) !== false) {
                            $toArr = explode(',', trim($filter[1], '[]'));
                            $query->whereIn($filter[0], $toArr);

                            continue;
                        }
                        $query->where($filter[0], $filter[1]);
                        break;

                    case '~':
                        $query->where($filter[0], 'like', "%{$filter[1]}%");
                        break;

                    default:
                        $query->where($filter[0], $match['mark'], $filter[1]);
                        break;
                }
            }
        });
    }

    /**
     * 格式化 sort 参数.
     *
     * @author 28youth
     * @param  @return  \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSortByQueryString(Builder $query): Builder
    {
        $sort = request()->get('sort', '');
        $sortby = array_filter(explode('-', $sort));
        return $query->when($sortby, function ($query) use ($sortby) {
            $query->orderBy($sortby[0], $sortby[1]);
        });
    }

    /**
     * 返回带分页信息的数据
     *
     * @author 28youth
     * @param  \Illuminate\Database\Eloquent\Builder
     * @param  integer $pagesieze
     * @return mixed
     */
    public function scopeWithPagination(Builder $query, int $pagesieze = 10): array
    {
        $items = $query->paginate($pagesieze);

        return [
            'data' => $items->items(),
            'total' => $items->count(),
            'page' => $items->currentPage(),
            'pagesize' => $items->perPage(),
            'totalpage' => $items->total(),
        ];
    }
}
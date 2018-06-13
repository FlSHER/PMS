<?php 

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

Trait FormatFilter
{
    /**
     * 格式化 filter 参数.
     *
     * @author 28youth
     * @return  \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFormatFilter(Builder $query, $filters): Builder
    {
        $filters = array_filter(explode(';', $filters));
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
     * 获取分页数据.
     * 
     * @author 28youth
     * @param  \Illuminate\Database\Eloquent\Builder
     * @param  integer $pagesieze
     * @return mixed
     */
    public function scopePagination(Builder $query, int $pagesieze = 10)
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
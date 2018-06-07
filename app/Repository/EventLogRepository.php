<?php 

namespace App\Repository;

use Illuminate\Http\Request;
use App\Models\EventLog as EventLogModel;

class EventLogRepository
{

	/**
	 * 获取事件奖扣纪录分页列表.
	 * 
	 * @author 28youth
	 * @param  \Illuminate\Http\Request $request
	 * @return mixed
	 */
	public function getPaginateList(Request $request)
	{	
		$sort =  $request->query('sort');
		$limit = $request->query('limit', 20);
		$items = $this->setCondition($request)
			->when(isset($sort), function ($query) use ($sort) {
				$sort = explode('-', $sort);
				$query->orderBy($sort[0], $sort[1]);
			})
			->paginate($limit);

		return [
			'data' => $items->items(),
			'total' => $items->count(),
			'page' => $items->currentPage(),
			'pagesize' => $limit,
			'totalpage' => $items->total(),
		];
	}

	/**
	 * 获取全部事件奖扣纪录列表.
	 * 
	 * @author 28youth
	 * @param  \Illuminate\Http\Request $request
	 * @return mixed
	 */
	public function getList(Request $request)
	{
		$sort =  $request->query('sort');

		return $this->setCondition($request)
			->when(isset($sort), function ($query) use ($sort) {
					$sort = explode('-', $sort);
					$query->orderBy($sort[0], $sort[1]);
				})
			->get();
	}

	/**
	 * filter 条件过滤.
	 * 
	 * @author 28youth
	 * @param  \Illuminate\Http\Request $request
	 * @return mixed
	 */
	public function setCondition(Request $request)
	{
		$filters = explode(';', $request->query('filters'));
		$builder = EventLogModel::query();

		foreach ($filters as $key => $value) {
			preg_match('/(=|~|>=|>|<=|<)/', $value, $match);
			$filter = explode($match[0], $value);

			switch ($match[0]) {
				case '=':
					$toArr = explode(',', $filter[1]);
					if (count($toArr) > 1) {
						$builder->whereIn($filter[0], $toArr);
						continue;
					}
					$builder->where($filter[0], $filter[1]);
					break;

				case '~':
					$builder->where($filter[0], 'like', "%{$filter[1]}%");
					break;

				default:
					$builder->where($filter[0], $match[0], $filter[1]);
					break;
			}
		}

		return $builder;
	}

}
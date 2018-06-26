<?php 

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\CommonConfig;

class BasePointController extends Controller
{
	
	/**
	 * 获取基础分配置.
	 * 
	 * @author 28youth
	 * @param  \Illuminate\Http\Request $request
	 * @return mixed
	 */
	public function index(Request $request)
	{
		$type = $request->query('type', 'education');

		$datas = CommonConfig::byNamespace('basepoint')
			->where('name', sprintf('basepoint:%s', $type))
			->first();

		return response()->json($datas ? json_decode($datas->value) : [], 200);
	}

	/**
	 * 存储基础分配置.
	 * 
	 * @author 28youth
	 * @param  \Illuminate\Http\Request $request
	 * @return mixed
	 */
	public function store(Request $request)
	{
		$rules = [
            'datas' => 'required|array',
            'type' => 'required|string|in:type,education,position,speciality',
            'datas.*.point' => 'required|integer|min:0'
        ];
        $messages = [
            'datas.required' => '输入的选项内容不能为空',
            'type.required' => '输入的选项类型不能为空', 
            'datas.*.point.required' => '输入的配置分不能为空',
            'datas.*.point.integer' => '输入的配置分必须为一个数字',
            'datas.*.point.min' => '输入的配置分不能小于0'
        ];
        $this->validate($request, $rules, $messages);

        $type = $request->input('type');
        $datas = $request->input('datas');

        CommonConfig::updateOrCreate(
            ['namespace' => 'basepoint', 'name' => sprintf('basepoint:%s', $type)],
            ['value' => json_encode($datas)]
        );

        return response()->json(null, 201);
	}
}
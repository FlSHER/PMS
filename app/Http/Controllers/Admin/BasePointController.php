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
		$name = $request->query('name', 'education');

		$datas = CommonConfig::byNamespace('basepoint')
			->where('name', $name)
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
            'name' => 'required|string|in:name,education,position,speciality,seniority',
            'datas.*.point' => 'required|integer|min:0'
        ];
        $messages = [
            'datas.required' => '输入的选项内容不能为空',
            'name.required' => '输入的选项名称不能为空', 
            'datas.*.point.required' => '输入的配置分不能为空',
            'datas.*.point.integer' => '输入的配置分必须为一个数字',
            'datas.*.point.min' => '输入的配置分不能小于0'
        ];
        $this->validate($request, $rules, $messages);

        $name = $request->input('name');
        $datas = $request->input('datas');

        CommonConfig::updateOrCreate(
            ['namespace' => 'basepoint', 'name' => $name],
            ['value' => json_encode($datas)]
        );

        return response()->json(null, 201);
	}


	/**
     * 配置单个规则内容.
     *
	 * @author 28youth
	 * @param  \Illuminate\Http\Request $request
	 * @return mixed
     */
    public function storeSingle(Request $request)
    {
    	$rules = [
            'name' => 'required|string|in:name,max_point,ratio',
            'value' => 'required',
            'datas' => 'nullable|array',
        ];
        $messages = [
            'name.required' => '输入的选项名称不能为空', 
            'value.required' => '输入的配置值不能为空',
        ];
        $this->validate($request, $rules, $messages);
        
        $datas = $request->input('datas', '');

        if ($datas) {
        	collect($datas)->map(function ($data) {
		        CommonConfig::updateOrCreate(
		            ['namespace' => 'basepoint', 'name' => $data['name']],
		            ['value' => $data['value']]
		        );
        	});
        } else {
	        CommonConfig::updateOrCreate(
	            ['namespace' => 'basepoint', 'name' => $request->input('name')],
	            ['value' => $request->input('value')]
	        );
        }

        return response()->json(['message' => '操作成功'], 201);
    }
}
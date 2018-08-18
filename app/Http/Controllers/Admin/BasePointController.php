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

        $items = app()->call([$this, camel_case('get_'.$name)]);

        return response()->json($items, 200);
    }
	
	/**
	 * 获取学历分配置.
	 * 
	 * @author 28youth
	 * @return App\Models\CommonConfig
	 */
	public function getEducation()
	{
        $data = CommonConfig::byNamespace('basepoint')
            ->where('name', 'education')
            ->first();

        return $data ? json_decode($data->value) : [];
	}

    /**
     * 获取职位分配置.
     * 
     * @author 28youth
     * @return App\Models\CommonConfig
     */
    public function getPosition(Request $request)
    {
        $data = CommonConfig::byNamespace('basepoint')
            ->where('name', 'position')
            ->first();

        return $data ? json_decode($data->value) : [];
    }

    /**
     * 获取工龄分配置.
     * 
     * @author 28youth
     * @return App\Models\CommonConfiged
     */
    public function getSeniority(Request $request)
    {
        return CommonConfig::byNamespace('basepoint')
            ->select(['name', 'value'])
            ->whereIn('name', ['max_point', 'ratio'])
            ->get();
    }

    /**
     * 获取考勤转积分配置.
     * 
     * @author 28youth
     * @return App\Models\CommonConfiged
     */
    public function getAttendance(Request $request)
    {
        return CommonConfig::byNamespace('basepoint')
            ->select(['name', 'value'])
            ->where('name', 'attendance_radio')
            ->get();
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
            'data' => 'required|array',
            'name' => 'required|string|in:name,education,position,seniority',
            'data.*.point' => 'required|integer|min:0'
        ];
        $messages = [
            'data.required' => '输入的选项内容不能为空',
            'name.required' => '输入的选项名称不能为空', 
            'data.*.point.required' => '输入的配置分不能为空',
            'data.*.point.integer' => '输入的配置分必须为一个数字',
            'data.*.point.min' => '输入的配置分不能小于0'
        ];
        $this->validate($request, $rules, $messages);

        $name = $request->input('name');
        $data = $request->input('data');

        CommonConfig::updateOrCreate(
            ['namespace' => 'basepoint', 'name' => $name],
            ['value' => json_encode($data)]
        );

        return response()->json(null, 201);
	}


	/**
     * 配置工龄上限.
     *
	 * @author 28youth
	 * @param  \Illuminate\Http\Request $request
	 * @return mixed
     */
    public function storeSeniority(Request $request)
    {
    	$rules = [
            'data' => 'required|array',
            'data.*.name' => 'required|string',
            'data.*.value' => 'required'
        ];
        $messages = [
            'data.*.name.required' => '输入的选项名称不能为空', 
            'data.*.value.required' => '输入的配置值不能为空',
        ];
        $this->validate($request, $rules, $messages);
        
        $data = $request->input('data', '');

        collect($data)->map(function ($data) {
            CommonConfig::updateOrCreate(
                ['namespace' => 'basepoint', 'name' => $data['name']],
                ['value' => $data['value']]
            );
        });

        return response()->json(['message' => '操作成功'], 201);
    }

    /**
     * 设置考勤转积分比例.
     * 
     * @author 28youth
     * @param  Request $request
     * @return mixed
     */
    public function storeAttendance(Request $request)
    {
        $rules = [
            'data' => 'required|array',
            'data.*.point' => 'required|min:1',
            'data.*.time' => 'required|min:1'
        ];
        $message = [
            'data.*.point.required' => '输入的积分值不能为空',
            'data.*.point.min' => '输入的积分值不能小于 :min',
            'data.*.time.required' => '输入的时间不能为空',
            'data.*.time.min' => '输入的时间不能小于 :min 分钟',
        ];

        $this->validate($request, $rules, $messages);

        CommonConfig::updateOrCreate(
            ['namespace' => 'basepoint', 'name' => 'attendance_radio'],
            ['value' => json_encode($data)]
        );

        return response()->json(['message' => '操作成功'], 201);
    }
}
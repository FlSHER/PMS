<?php 

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\CertificateStaff;
use App\Models\Certificate as CertificateModel;

class CertificateController extends Controller
{
	/**
	 * 获取证书.
	 * 
	 * @author 28youth
	 * @param  \Illuminate\Http\Request $request
	 * @return mixed
	 */
	public function index(Request $request)
	{
		$items = CertificateModel::query()
			->filterByQueryString()
			->withPagination();

		return response()->json($items);
	}

	/**
	 * 获取一个证书信息.
	 * 
	 * @author 28youth
	 * @param  \App\Models\Certificate $certificate
	 * @return mixed
	 */
	public function show(CertificateModel $certificate)
	{
		return response()->json($certificate);
	}

	/**
	 * 添加一个证书.
	 * 
	 * @author 28youth
	 * @param  \Illuminate\Http\Request $request
	 * @param  \App\Models\Certificate $certificate
	 * @return mixed
	 */
	public function store(Request $request, CertificateModel $certificate)
	{
		$rules = [
			'name' => 'required|string|unique:certificates,name',
			'point' => 'required|integer'
		];
		$messages = [
			'name.required' => '证书名称不能为空',
			'name.unique' => '证书名称已存在',
			'point.required' => '基础分配置不能为空'
		];
		$this->validate($request, $rules, $messages);

		$certificate->fill($request->all());
		$certificate->save();

		return response()->json(['message' => '添加成功'], 201);
	}

	/**
	 * 修改一个证书.
	 * 
	 * @author 28youth
	 * @param  \Illuminate\Http\Request $request
	 * @param  \App\Models\Certificate $certificate
	 * @return mixed
	 */
	public function update(Request $request, CertificateModel $certificate)
	{
		$rules = [
			'name' => [
				'nullable', 
				Rule::unique('certificates', 'name')->ignore($certificate->id)
			],
			'point' => 'nullable|integer'
		];
		$messages = [
			'name.unique' => '证书名称已存在',
		];
		$this->validate($request, $rules, $messages);

		$certificate->fill($request->all());
		$certificate->save();

		return response()->json(['message' => '编辑成功'], 201);
	}

	/**
	 * [award description]
	 * @author 28youth
	 * @param  Request          $request [description]
	 * @param  CertificateStaff $model   [description]
	 * @return [type]                    [description]
	 */
	public function award(Request $request, CertificateStaff $model)
	{
		$rules = [
			'staff_sn' => 'required|integer',
			'certificate_id' => 'required|integer|exists:certificates,id'
		];
		$messages = [
			'staff_sn.required' => '员工编号不能为空',
			'certificate_id.required' => '证书编号不能为空',
			'certificate_id.exists' => '证书编号错误'
		];

		$this->validate($request, $rules, $messages);

		$model->staff_sn = $request->staff_sn;
		$model->certificate_id = $request->certificate_id;
		$model->save();

		return response()->json($model, 201);
	}
}
<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        return response()->json($certificate, 201);
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

        return response()->json($certificate, 201);
    }


    /**
     * 删除证书(关联删除证书拥有者).
     *
     * @author 28youth
     * @param  \App\Models\Certificate $certificate
     * @return null
     */
    public function delete(CertificateModel $certificate)
    {
        $certificate->getConnection()->transaction(function () use ($certificate) {
            $certificate->staff()->delete();
            $certificate->delete();
        });

        return response()->json(null, 204);
    }


    /**
     * 获取全部证书拥有者
     *
     * @author Fisher
     * @return \Illuminate\Support\Collection|static
     */
    public function getCertificateStaff()
    {
        $certificateStaff = CertificateStaff::all();
        $allStaffSn = $certificateStaff->pluck('staff_sn')->unique()->values();
        $staff = app('api')->getStaff(['filters' => 'staff_sn=' . json_encode($allStaffSn)]);
        $staffKeyBySn = array_combine(array_pluck($staff, 'staff_sn'), $staff);
        return $certificateStaff->map(function ($value) use ($staffKeyBySn) {
            return array_collapse([$value->toArray(), $staffKeyBySn[$value->staff_sn]]);
        });
    }

    /**
     * 批量分配证书
     *
     * @author Fisher
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function storeCertificateStaff(Request $request)
    {
        $rules = [
            'data.*.staff_sn' => 'required|integer',
            'data.*.certificate_id' => 'required|integer|exists:certificates,id'
        ];
        $messages = [
            'data.*.staff_sn.required' => '员工编号不能为空',
            'data.*.certificate_id.required' => '证书编号不能为空',
            'data.*.certificate_id.exists' => '证书编号错误'
        ];
        $this->validate($request, $rules, $messages);

        $allStaffSn = array_values(array_unique(array_pluck($request->input('data'), 'staff_sn')));
        $staff = app('api')->getStaff(['filters' => 'staff_sn=' . json_encode($allStaffSn)]);
        if (count($staff) === count($allStaffSn)) {
            $staffKeyBySn = array_combine(array_pluck($staff, 'staff_sn'), $staff);
            DB::beginTransaction();
            $response = array_map(function ($data) use ($staffKeyBySn) {
                $certificateStaff = CertificateStaff::updateOrCreate($data, [
                    'staff_sn' => $data['staff_sn'], 'certificate_id' => $data['certificate_id']
                ]);
                return array_collapse([$certificateStaff->toArray(), $staffKeyBySn[$certificateStaff->staff_sn]]);
            }, $request->data);
            DB::commit();
            return response()->json($response, 201);
        } else {
            abort(404, '员工不存在');
        }
    }

    /**
     * 批量删除证书拥有者
     *
     * @author Fisher
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteCertificateStaff(Request $request)
    {
        $rules = [
            'keys' => ['required', 'array']
        ];
        $messages = [
            'keys.required' => '主键不能为空',
            'keys.array' => '主键必须是数组',
        ];
        $this->validate($request, $rules, $messages);
        $certificateStaffQuery = CertificateStaff::whereIn(\DB::raw('(certificate_id,staff_sn)'), array_map(function ($value) {
            return \DB::raw(preg_replace('/^(\d+)\-(\d+)$/', '($1,$2)', $value));
        }, $request->keys));
        if ($certificateStaffQuery->count() === count($request->keys)) {
            $certificateStaffQuery->delete();
            return response()->json(null, 204);
        } else {
            abort(404, '未找到记录');
        }
    }

}
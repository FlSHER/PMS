<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class EventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->jurisdiction();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name'=>'required',
            'type_id'=>'required|numeric',
            'point_a_min'=>'required|numeric',
            'point_a_max'=>'required|numeric',
            'point_a_default'=>'required|numeric',
            'point_b_min'=>'required|numeric',
            'point_b_max'=>'required|numeric',
            'point_b_default'=>'required|numeric',
//            'first_approver_sn'=>'required|numeric',
//            'first_approver_name'=>'required|',
//            'final_approver_sn'=>'required|numeric',
//            'final_approver_name'=>'required',
            'first_approver_locked'=>'required|min:0|max:1',//0未锁定1锁定
            'final_approver_locked'=>'required|min:0|max:1',//0未锁定1锁定
            'default_cc_addressees'=>'nullable',
            'is_active'=>'required|min:0|max:1'//0未激活1激活
        ];
    }

    public function attributes()
    {
        return [
            'name'=>'事件名称',
            'type_id'=>'事件类型',
            'point_a_min'=>'A分最小值',
            'point_a_max'=>'A分最大值',
            'point_a_default'=>'A分默认值',
            'point_b_min'=>'B分最小值',
            'point_b_max'=>'B分最大值',
            'point_b_default'=>'B分默认值',
//            'first_approver_sn'=>'初审人编号',
//            'first_approver_name'=>'初审人姓名',
//            'final_approver_sn'=>'终审人编号',
//            'final_approver_name'=>'终审人姓名',
            'first_approver_locked'=>'初审人锁定',//0未锁定1锁定
            'final_approver_locked'=>'终审人锁定',//0未锁定1锁定
            'default_cc_addressees'=>'默认抄送人',
            'is_active'=>'是否激活'//0未激活1激活
            ];
    }
    /**
     * 权限
     */
    public function jurisdiction()
    {
        return true;
    }
}

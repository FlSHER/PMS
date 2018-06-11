<?php 

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventLogRequest extends FormRequest
{
	
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'point_a' => 'required|integer',
            'point_b' => 'required|integer',
            'first_approver_sn' => 'required|integer',
            'first_approver_name' => 'required|string',
            'final_approver_sn' => 'required|integer',
            'final_approver_name' => 'required|string',
            'recorder_point' => 'required|integer',
        ];
    }

    /**
     * Get rule messages.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
        	'point_a.required' => 'A分值不能为空',
        	'point_b.required' => 'B分值不能为空',
        	'first_approver_sn.required' => '初审人编号不能为空',
        	'first_approver_name.required' => '初审人姓名不能为空',
        	'final_approver_sn.required' => '终审人编号不能为空',
        	'final_approver_name.required' => '终审人姓名不能为空',
        	'recorder_point.required' => '记录人得分不能为空',
        ];
    }

}
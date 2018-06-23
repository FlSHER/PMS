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
            'event_id' => 'required|integer|exists:event,id',
            'first_approver_sn' => 'required|integer',
            'first_approver_name' => 'required|string',
            'final_approver_sn' => 'required|integer',
            'final_approver_name' => 'required|string',
            'addressees' => 'required|array',
            'participants' => 'required|array'
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
            'event_id.required' => '事件编号不能为空',
            'event_id.exists' => '事件编号不存在',
            'addressees.required' => '抄送人不能为空',
            'participants.required' => '事件参与人不能为空',
        	'first_approver_sn.required' => '初审人编号不能为空',
        	'first_approver_name.required' => '初审人姓名不能为空',
        	'final_approver_sn.required' => '终审人编号不能为空',
        	'final_approver_name.required' => '终审人姓名不能为空'
        ];
    }

}
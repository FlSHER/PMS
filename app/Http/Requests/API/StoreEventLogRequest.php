<?php

namespace App\Http\Requests\API;

use Illuminate\Validation\Rule;
use App\Rules\ValidateParticipant;
use App\Models\Event as EventModel;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\FinalApprover as FinalApproverModel;

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
            'first_approver_sn' => [
                'bail',
                'required',
                'integer'
            ],
            'final_approver_sn' => [
                'bail',
                'required',
                'integer'
            ],
            'events.*.event_id' => [
                'bail',
                'required',
                'integer',
                'exists:events,id'
            ],
            'events.*.participants' => [
                'bail',
                'required',
                'array'
            ],
            'events' => [new ValidateParticipant($this->all())],
            'executed_at' => 'bail|required|date|before:' . date('Y-m-d H:i'),
            'first_approver_name' => 'required|string',
            'final_approver_name' => 'required|string',
            'addressees' => 'nullable|array',
            
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
            'events.*.event_id.required' => '事件编号不能为空',
            'events.*.event_id.exists' => '事件编号不存在',
            'first_approver_sn.required' => '初审人编号不能为空',
            'first_approver_name.required' => '初审人姓名不能为空',
            'final_approver_sn.required' => '终审人编号不能为空',
            'final_approver_name.required' => '终审人姓名不能为空',
            'addressees.array' => '抄送人格式错误',
            'events.*.participants.required' => '事件参与人不能为空',
            'events.*.participants.array' => '事件参与人格式错误',
            'executed_at.required' => '事件执行时间不能为空',
            'executed_at.before' => '事件执行时间不能大于当前时间',
        ];
    }

}
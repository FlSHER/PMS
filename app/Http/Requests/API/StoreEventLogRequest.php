<?php 

namespace App\Http\Requests\API;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\Event as EventModel;

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
        $event = EventModel::find($this->event_id);

        return [
            'first_approver_sn' => [
                'required',
                'integer',
                function($attribute, $value, $fail) use ($event) {
                    if ($event->first_approver_locked === 1 && $event->first_approver_sn !== $value) {
                        return $fail('终审人已锁定为'.$event->first_approver_name);
                    }
                }
            ],
            'final_approver_sn' => [
                'required',
                'integer',
                Rule::notIn([$this->first_approver_sn, $this->user()->staff_sn]),
                function($attribute, $value, $fail) use ($event) {
                    if ($event->final_approver_locked === 1 && $event->final_approver_sn !== $value) {
                        return $fail('终审人已锁定为'.$event->final_approver_name);
                    }
                }
            ],
            'event_id' => 'required|integer|exists:event,id',
            'first_approver_name' => 'required|string',
            'final_approver_name' => 'required|string',
            'addressees' => 'nullable|array',
            'participants' => 'required|array',
            'participants.*.point_a' => [
                'integer', 
                'max:'.$event->point_a_max, 
                'min:'.$event->point_a_min 
            ],
            'participants.*.point_b' => [
                'integer', 
                'max:'.$event->point_b_max, 
                'min:'.$event->point_b_min 
            ],
            'executed_at' => 'required|date|before:'.date('Y-m-d H:i')
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
            'first_approver_sn.required' => '初审人编号不能为空',
            'first_approver_name.required' => '初审人姓名不能为空',
            'final_approver_sn.required' => '终审人编号不能为空',
            'final_approver_sn.not_in' => '终审人不能是初审人和记录人',
            'final_approver_name.required' => '终审人姓名不能为空',
            'addressees.array' => '抄送人格式错误',
            'participants.required' => '事件参与人不能为空',
            'participants.*.point_a.max' => '参与人 A 分不能大于默认值',
            'participants.*.point_a.min' => '参与人 A 分不能小于默认值',
            'participants.*.point_b.max' => '参与人 B 分不能大于默认值',
            'participants.*.point_b.min' => '参与人 B 分不能小于默认值',
            'executed_at.required' => '事件执行时间不能为空',
            'executed_at.before' => '事件执行时间不能大于当前时间'
        ];
    }

}
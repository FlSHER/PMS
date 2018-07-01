<?php 

namespace App\Http\Requests\API;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\Event as EventModel;
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
        $event = EventModel::find($this->event_id);
        $final = FinalApproverModel::where('staff_sn', $this->final_approver_sn)->first();

        return [
            'first_approver_sn' => [
                'bail',
                'required',
                'integer',
                function($attribute, $value, $fail) use ($event) {
                    if ($event->first_approver_locked === 1 && $event->first_approver_sn != $value) {
                        return $fail('初审人已锁定为'.$event->first_approver_name);
                    }
                }
            ],
            'final_approver_sn' => [
                'bail',
                'required',
                'integer',
                Rule::notIn([$this->first_approver_sn, $this->user()->staff_sn]),
                function($attribute, $value, $fail) use ($event) {
                    if ($event->final_approver_locked === 1 && $event->final_approver_sn != $value) {
                        return $fail('终审人已锁定为'.$event->final_approver_name);
                    }
                }
            ],
            'participants.*.point_a' => [
                'bail',
                'integer',
                function($attribute, $value, $fail) use ($final) {
                    if ($value > 0 && $value > $final->point_a_awarding_limit) {
                        $fail('终审人无权限');
                    }
                    if ($value < 0 && $value < $final->point_a_deducting_limit){
                        return $fail('终审人无权限');
                    }
                },
                'max:'.$event->point_a_max, 
                'min:'.$event->point_a_min 
            ],
            'participants.*.point_b' => [
                'bail',
                'integer', 
                'max:'.$event->point_b_max, 
                'min:'.$event->point_b_min,
                function($attribute, $value, $fail) use ($final) {
                    if ($value > 0 && $value > $final->point_b_awarding_limit) {
                        $fail('终审人无权限');
                    }
                    if ($value < 0 && $value < $final->point_b_deducting_limit){
                        return $fail('终审人无权限');
                    }
                },
            ],
            'executed_at' => 'bail|required|date|before:'.date('Y-m-d H:i'),
            'event_id' => 'required|integer|exists:events,id',
            'first_approver_name' => 'required|string',
            'final_approver_name' => 'required|string',
            'addressees' => 'nullable|array',
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
            'first_approver_sn.required' => '初审人编号不能为空',
            'first_approver_name.required' => '初审人姓名不能为空',
            'final_approver_sn.required' => '终审人编号不能为空',
            'final_approver_sn.not_in' => '终审人不能是初审人和记录人',
            'final_approver_name.required' => '终审人姓名不能为空',
            'addressees.array' => '抄送人格式错误',
            'participants.required' => '事件参与人不能为空',
            'participants.*.point_a.max' => '参与人 A 分不能大于默认值:max',
            'participants.*.point_a.min' => '参与人 A 分不能小于默认值:min',
            'participants.*.point_b.max' => '参与人 B 分不能大于默认值:max',
            'participants.*.point_b.min' => '参与人 B 分不能小于默认值:min',
            'executed_at.required' => '事件执行时间不能为空',
            'executed_at.before' => '事件执行时间不能大于当前时间'
        ];
    }

}
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRevisionRequest extends FormRequest
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
    public function rules()
    {
        return [
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'breaks.*.start_time' => 'nullable|date_format:H:i',
            'breaks.*.end_time' => 'nullable|date_format:H:i',
            'note' => 'required|string',
        ];
    }

    /**
     * カスタムバリデーションルールを追加
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
           
            if ($this->filled(['start_time', 'end_time']) && $this->start_time > $this->end_time) {
                $validator->errors()->add('time_error', '出勤時間もしくは退勤時間が不適切な値です');
            }

            if (isset($this->breaks) && is_array($this->breaks)) {
                foreach ($this->breaks as $index => $break) {
                 
                    if (empty($break['start_time']) && empty($break['end_time'])) {
                        continue;
                    }

                    if ((empty($break['start_time']) && !empty($break['end_time'])) ||
                        (!empty($break['start_time']) && empty($break['end_time']))
                    ) {
                        $validator->errors()->add('break_pair_error', '休憩開始時間と休憩終了時間は両方入力してください');
                        continue;
                    }

                
                    if (!empty($break['start_time']) && !empty($break['end_time'])) {
                        
                        if ($this->filled(['start_time', 'end_time']) && $this->start_time <= $this->end_time) {
            
                            if ($break['start_time'] < $this->start_time || $break['start_time'] > $this->end_time) {
                                $validator->errors()->add('break_out_of_range', '休憩時間が勤務時間外です');
                            }

                            if ($break['end_time'] < $this->start_time || $break['end_time'] > $this->end_time) {
                                $validator->errors()->add('break_out_of_range', '休憩時間が勤務時間外です');
                            }
                        }

                        if ($break['start_time'] > $break['end_time']) {
                            $validator->errors()->add('break_time_error', '休憩時間が勤務時間外です');
                        }
                    }
                }
            }
        });
    }

    /**
     * バリデーションエラーメッセージのカスタマイズ
     *
     * @return array
     */
    public function messages()
    {
        return [
            'note.required' => '備考を記入してください',
            'start_time.required' => '出勤時間を入力してください',
            'end_time.required' => '退勤時間を入力してください',
            'start_time.date_format' => '出勤時間の形式が正しくありません',
            'end_time.date_format' => '退勤時間の形式が正しくありません',
            'breaks.*.start_time.date_format' => '休憩時間が勤務時間外です。',
            'breaks.*.end_time.date_format' => '休憩時間が勤務時間外です。',
        ];
    }
}

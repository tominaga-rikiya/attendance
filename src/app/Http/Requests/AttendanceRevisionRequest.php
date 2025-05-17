<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use Illuminate\Foundation\Http\FormRequest;

class AttendanceRevisionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $attendance = Attendance::find($this->route('id'));

        return [
            'start_time' => 'required|date_format:H:i',
            'end_time' => $attendance && $attendance->status === 'finished'
                ? 'required|date_format:H:i'
                : 'nullable|date_format:H:i',
            'breaks.*.start_time' => 'nullable|date_format:H:i',
            'breaks.*.end_time' => 'nullable|date_format:H:i',
            'note' => 'required|string',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->filled(['start_time', 'end_time']) && $this->start_time > $this->end_time) {
                $validator->errors()->add('time_error', '出勤時間もしくは退勤時間が不適切な値です');
            }

            if (isset($this->breaks) && is_array($this->breaks)) {
                $validBreaks = [];

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
                        if ($break['start_time'] > $break['end_time']) {
                            $validator->errors()->add('break_time_error', '休憩時間が不適切な値です');
                            continue;
                        }

                        if ($this->filled(['start_time', 'end_time']) && $this->start_time <= $this->end_time) {
                            if (
                                $break['start_time'] < $this->start_time || $break['start_time'] > $this->end_time ||
                                $break['end_time'] < $this->start_time || $break['end_time'] > $this->end_time
                            ) {
                                $validator->errors()->add('break_out_of_range', '休憩時間が勤務時間外です');
                            }
                        }

                        $validBreaks[] = [
                            'index' => $index,
                            'start_time' => $break['start_time'],
                            'end_time' => $break['end_time']
                        ];
                    }
                }

                // 重複チェック
                for ($i = 0; $i < count($validBreaks); $i++) {
                    for ($j = $i + 1; $j < count($validBreaks); $j++) {
                        $break1 = $validBreaks[$i];
                        $break2 = $validBreaks[$j];

                        if ($this->isOverlapping(
                            $break1['start_time'],
                            $break1['end_time'],
                            $break2['start_time'],
                            $break2['end_time']
                        )) {
                            $validator->errors()->add(
                                'break_overlap_error',
                                '休憩時間が重複しています。休憩' . ($break1['index'] + 1) .
                                    'と休憩' . ($break2['index'] + 1) . 'が重なっています'
                            );
                        }
                    }
                }
            }
        });
    }

    /**
     * 時間の重複をチェック
     */
    private function isOverlapping($start1, $end1, $start2, $end2)
    {
        if ($start1 >= $start2 && $start1 <= $end2) {
            return true;
        }

        if ($end1 >= $start2 && $end1 <= $end2) {
            return true;
        }

        if ($start1 <= $start2 && $end1 >= $end2) {
            return true;
        }

        if ($start2 <= $start1 && $end2 >= $end1) {
            return true;
        }

        return false;
    }

    public function messages()
    {
        return [
            'note.required' => '備考を記入してください',
            'start_time.required' => '出勤時間を入力してください',
            'end_time.required' => '退勤時間を入力してください',
            'start_time.date_format' => '出勤時間の形式が正しくありません',
            'end_time.date_format' => '退勤時間の形式が正しくありません',
            'breaks.*.start_time.date_format' => '休憩開始時間の形式が正しくありません',
            'breaks.*.end_time.date_format' => '休憩終了時間の形式が正しくありません',
        ];
    }
}

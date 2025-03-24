<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use Carbon\Carbon;
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
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        // リクエストのアクションタイプを取得
        $action = $this->route()->getActionMethod();

        // アクションによって異なるルールを返す
        switch ($action) {
            case 'clockIn':
                return $this->getClockInRules();
            case 'clockOut':
                return $this->getClockOutRules();
            case 'breakStart':
                return $this->getBreakStartRules();
            case 'breakEnd':
                return $this->getBreakEndRules();
            default:
                return [];
        }
    }

    /**
     * 出勤時のバリデーションルール
     */
    private function getClockInRules()
    {
        return [];
    }

    /**
     * 退勤時のバリデーションルール
     */
    private function getClockOutRules()
    {
        return [];
    }

    /**
     * 休憩開始時のバリデーションルール
     */
    private function getBreakStartRules()
    {
        return [];
    }

    /**
     * 休憩終了時のバリデーションルール
     */
    private function getBreakEndRules()
    {
        return [];
    }

    /**
     * バリデーションが完了した後の処理
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $action = $this->route()->getActionMethod();
            $user = auth()->user();
            $today = Carbon::now()->toDateString();
            $attendance = Attendance::where('user_id', $user->id)
                ->where('date', $today)
                ->first();

            switch ($action) {
                case 'clockIn':
                    $this->validateClockIn($validator, $attendance);
                    break;
                case 'clockOut':
                    $this->validateClockOut($validator, $attendance);
                    break;
                case 'breakStart':
                    $this->validateBreakStart($validator, $attendance);
                    break;
                case 'breakEnd':
                    $this->validateBreakEnd($validator, $attendance);
                    break;
            }
        });
    }

    /**
     * 出勤時の追加バリデーション
     */
    private function validateClockIn($validator, $attendance)
    {
        // 既に出勤済みの場合はエラー
        if ($attendance && $attendance->start_time) {
            $validator->errors()->add('start_time', '既に出勤済みです');
        }
    }

    /**
     * 退勤時の追加バリデーション
     */
    private function validateClockOut($validator, $attendance)
    {
        // 出勤していない場合はエラー
        if (!$attendance || $attendance->status !== Attendance::STATUS_WORKING) {
            $validator->errors()->add('status', '出勤中でないため退勤できません');
        }

        // 既に退勤済みの場合はエラー
        if ($attendance && $attendance->end_time) {
            $validator->errors()->add('end_time', '既に退勤済みです');
        }
    }

    /**
     * 休憩開始時の追加バリデーション
     */
    private function validateBreakStart($validator, $attendance)
    {
        // 出勤していない場合はエラー
        if (!$attendance || $attendance->status !== Attendance::STATUS_WORKING) {
            $validator->errors()->add('status', '出勤中でないため休憩に入れません');
        }
    }

    /**
     * 休憩終了時の追加バリデーション
     */
    private function validateBreakEnd($validator, $attendance)
    {
        // 休憩中でない場合はエラー
        if (!$attendance || $attendance->status !== Attendance::STATUS_ON_BREAK) {
            $validator->errors()->add('status', '休憩中でないため復帰できません');
        }
    }
}

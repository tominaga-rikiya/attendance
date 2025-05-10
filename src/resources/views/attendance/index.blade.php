@extends('layouts.app')

@section('title','勤怠一覧')

@section('css')
    <link rel="stylesheet" href="{{ asset('/css/index.css') }}">
@endsection

@section('content')
@include('components.header')
<div class="container">
    <!-- 日付ナビゲーション -->
    <div class="date-navigation">
        <div class="date-title">
            <h2 class="with-vertical-line">勤怠一覧</h2>
        </div>
    </div>

    <!-- 検索フォーム -->
    <div class="search-card">
        <div class="search-card-header"></div>
        <div class="search-card-body">
            <form method="GET" action="{{ route('attendance.index') }}" class="search-form">
                <div class="form-group">
                    <a href="{{ route('attendance.index', ['month' => date('Y-m', strtotime($searchMonth . '-01 -1 month'))]) }}" class="btn-primary">
                        ←前月
                    </a>
                    <div class="date-display">
                        <button type="button" class="calendar-icon" id="calendarToggle">
                            <i class="fas fa-calendar"></i>
                        </button>
                        <span>{{ Carbon\Carbon::parse($searchMonth . '-01')->format('Y/m') }}</span>
                        <input type="month" class="form-input hidden" id="month" name="month" value="{{ $searchMonth }}" onchange="this.form.submit()">
                    </div>
                    <a href="{{ route('attendance.index', ['month' => date('Y-m', strtotime($searchMonth . '-01 +1 month'))]) }}" class="btn-primary">
                        翌月→
                    </a>
                </div>
            </form>
        </div>
    </div>

   <!-- resources/views/attendance/index.blade.php -->

<!-- 勤怠一覧テーブル -->
<div class="table-card">
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>日付</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse($datesArray as $date => $attendance)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($date)->format('Y/m/d') }} ({{ \Carbon\Carbon::parse($date)->isoFormat('ddd') }})</td>
                        @if($attendance)
                            <!-- 出勤時間は常に表示 -->
                            <td class="text-center">{{ $attendance->time_only_start_time }}</td>
                            
                            <!-- 退勤時間はシンプルに表示 -->
                            <td class="text-center">
                                @if($attendance->end_time)
                                    {{ $attendance->time_only_end_time }}
                                @else
                                    
                                @endif
                            </td>
                            
                            <!-- 休憩時間 -->
                            <td class="text-center">{{ $attendance->formatted_break_time }}</td>
                            
                            <!-- 合計時間（退勤済みの場合のみ） -->
                            <td class="text-center">
                                @if($attendance->status === App\Models\Attendance::STATUS_FINISHED)
                                    {{ $attendance->formatted_work_time }}
                                @else
                                    
                                @endif
                            </td>
                            
                            <!-- 詳細ボタン -->
                            <td class="text-center">
                                <div class="button-group">
                                    <a href="{{ route('attendance.show', $attendance->id) }}" class="btn-outline">
                                        詳細
                                    </a>
                                </div>
                            </td>
                        @else
                            <td class="text-center"> </td>
                            <td class="text-center"> </td>
                            <td class="text-center"> </td>
                            <td class="text-center"> </td>
                            <td class="text-center"> </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">勤怠データがありません</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
@extends('layouts.app')

@section('title','勤怠')

@section('css')
    <link rel="stylesheet" href="{{ asset('/css/create.css') }}">
@endsection

@section('content')
@if ($status == "finished")
    @include('components.header_finished')
@else
    @include('components.header')
@endif

<div class="attendance-container text-center">
    <!-- ステータスバッジ -->
    <div class="status-badge">
        @if ($status == 'not_started')
            <span class="status-label status-not-started">勤務外</span>
        @elseif ($status == 'working')
            <span class="status-label status-working">出勤中</span>
        @elseif ($status == 'on_break')
            <span class="status-label status-on-break">休憩中</span>
        @else
            <span class="status-label status-finished">退勤済</span>
        @endif
    </div>

    <!-- 日付表示 -->
    <div class="date-display">
        {{ \Carbon\Carbon::now()->format('Y年n月j日') . '(' . ['日', '月', '火', '水', '木', '金', '土'][\Carbon\Carbon::now()->dayOfWeek] . ')' }}
    </div>

    <!-- 時間表示 -->
    <div class="time-display">
        {{ \Carbon\Carbon::now()->format('H:i') }}
    </div>

    <!-- 退勤済みの場合のみメッセージを表示（時刻表示の下） -->
    @if ($status == "finished")
    <div class="thank-you-message">
        お疲れ様でした。
    </div>
    @endif

    <!-- アクションボタン -->
    <div class="action-buttons">
        @if ($status == 'not_started')
            <form action="{{ route('attendance.clock-in') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-action btn-clock-in">出勤</button>
            </form>
        @elseif ($status == 'working')
            <div class="button-group">
                <form action="{{ route('attendance.clock-out') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-action btn-clock-out">退勤</button>
                </form>
                
                <form action="{{ route('attendance.break-start') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-action btn-break-start">休憩入</button>
                </form>
            </div>
        @elseif ($status == 'on_break')
            <form action="{{ route('attendance.break-end') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-action btn-break-end">休憩戻</button>
            </form>
        @endif
    </div>
</div>
@endsection
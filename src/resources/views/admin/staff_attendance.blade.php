@extends('layouts.app')

@section('title', $user->name . 'さんの勤怠')

@section('css')
    <link rel="stylesheet" href="{{ asset('/css/index.css') }}">
@endsection

@section('content')
    @include('components.admin_header')
    
    <div class="container">
        <div class="date-navigation">
            <div class="date-title">
                <h2 class="with-vertical-line">{{ $user->name }}さんの勤怠</h2>
            </div>
        </div>

        <div class="search-card">
            <div class="search-card-body">
                <form method="GET" action="{{ route('staff.attendance', $user->id) }}" class="search-form">
                    <div class="form-group">
                        <a href="{{ route('staff.attendance', ['user' => $user->id, 'month' => $prevMonth]) }}" class="btn-primary">
                            ← 前月
                        </a>
                         <div class="date-display">
                        <button type="button" class="calendar-icon" id="calendarToggle">
                            <i class="fas fa-calendar"></i>
                        </button>
                        <span>{{ Carbon\Carbon::parse($searchMonth . '-01')->format('Y/m') }}</span>
                        <input type="month" class="form-input hidden" id="month" name="month" value="{{ $searchMonth }}" onchange="this.form.submit()">
                    </div>
                        <a href="{{ route('staff.attendance', ['user' => $user->id, 'month' => $nextMonth]) }}" class="btn-primary">
                            翌月 →
                        </a>
                    </div>
                </form>
            </div>
        </div>

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
                        @foreach($datesArray as $date => $attendance)
                            <tr>
                                <td>
                                    {{ Carbon\Carbon::parse($date)->format('m/d')}}（{{ ['日', '月', '火', '水', '木', '金', '土'][Carbon\Carbon::parse($date)->dayOfWeek] }}）
                                </td>
                                @if($attendance)
                                    <td class="text-center">
                                        @if($attendance)
                                            {{ $attendance->time_only_start_time }}
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($attendance)
                                            {{ $attendance->time_only_end_time }}
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $attendance->formatted_break_time }}</td>
                                    <td class="text-center">
                                        @if($attendance->start_time && $attendance->end_time)
                                            {{ $attendance->formatted_work_time }}
                                        @else
                                            
                                        @endif
                                    </td>
                                    <td class="text-center">
                                <div class="button-group">
                                    <a href="{{ route('admin.attendances.show', $attendance->id) }}" class="btn-outline">
                                        詳細
                                    </a>
                                </div>
                            </td>
                                @else
                                    <td class="text-center"></td>
                                    <td class="text-center"></td>
                                    <td class="text-center"></td>
                                    <td class="text-center"></td>
                                    <td class="text-center"></td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="button-container" style="margin-top: 20px;">
            <a href="{{ route('staff.attendance.export', ['user' => $user->id, 'month' => $searchMonth]) }}" 
               class="btn-success">
                <i class="fas fa-file-csv"></i> CSV出力
            </a>
        </div>
    </div>

    <style>
        .button-container {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
        .btn-success {
            padding: 10px 20px;
            background-color: #000000;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-success:hover {
            background-color: #218838;
            text-decoration: none;
            color: white;
        }
    </style>    
@endsection
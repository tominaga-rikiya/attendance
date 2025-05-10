@extends('layouts.app')

@section('title','管理者一覧')

@section('css')
    <link rel="stylesheet" href="{{ asset('/css/index.css') }}">
@endsection

@section('content')
    @include('components.admin_header')
    
    <div class="container">
        <div class="date-navigation">
            <div class="date-title">
                <h2 class="with-vertical-line">{{ $formattedDate }}の勤怠</h2>
            </div>
        </div>

        <div class="search-card">
            <div class="search-card-body">
                <div class="form-group">
                    <a href="{{ route('admin.attendances.index', ['date' => $prevDate]) }}" class="btn-primary">
                        ← 前日
                    </a>
                    <div class="date-display">
                        <div class="calendar-container">
                            <span class="calendar-emoji">📅</span>
                            <span class="date-text">{{ date('Y/m/d', strtotime($searchDate)) }}</span>
                        </div>
                    </div>
                    <a href="{{ route('admin.attendances.index', ['date' => $nextDate]) }}" class="btn-primary">
                        翌日 →
                    </a>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>氏名</th>
                            <th>出勤</th>
                            <th>退勤</th>
                            <th>休憩</th>
                            <th>合計</th>
                            <th>詳細</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendances->whereNotNull('start_time') as $attendance)
                            <tr>
                                <td>{{ $attendance->user->name }}</td>
                                <td class="text-center">{{ $attendance->time_only_start_time }}</td>
                                <td class="text-center">{{ $attendance->time_only_end_time ?? '' }}</td>
                                <td class="text-center">
                                    @if($attendance->status == App\Models\Attendance::STATUS_ON_BREAK && !$attendance->breakTimes->last()->end_time)
                                        
                                    @else
                                        {{ $attendance->formatted_break_time }}
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($attendance->start_time && $attendance->end_time)
                                        {{ $attendance->formatted_work_time }}
                                    @else
                                        
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('admin.attendances.show', $attendance->id) }}" class="btn-outline">
                                        詳細
                                    </a>
                                </td>
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
    </div>
<style>
body {
    background-color: #F0EFF2;
    color: #737373;
    font-weight: bold;
}


.container {
    width: 95%;
    max-width: 1100px;
    margin: 0 auto;
    padding: 10px;
}


.date-navigation {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 30px;
    margin-bottom: 20px;
}

.with-vertical-line {
    border-left: 4px solid #333;
    padding-left: 10px;
    margin-left: 0;
    color: #000000;
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 15px;
    margin-top: 5px;
}


.search-card {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 1100px;
    margin-left: auto;
    margin-right: auto;
}

.search-card-body {
    padding: 10px;
}

.form-group {
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    min-height: 40px;
}


.date-display {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 20px;
    font-weight: bold;
    color: #000000;
}

.calendar-container {
    display: flex;
    align-items: center;
    gap: 10px;
}

.calendar-emoji {
    font-size: 20px;
}

.date-text {
    font-size: 18px;
}

.btn-primary {
    color: #333;
    text-decoration: none;
    padding: 6px 12px;
    background: transparent;
    border: none;
}

.btn-primary:hover {
    text-decoration: underline;
}


.table-card {
    background: #fff;
    border: 1px solid #e0e0e8;
    border-radius: 8px;
    margin-bottom: 10px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 1100px;
    margin-left: auto;
    margin-right: auto;
    overflow: hidden;
}

.table-container {
    overflow-x: auto;
}


.data-table {
    width: 100%;
    table-layout: fixed;
    border-collapse: separate;
    border-spacing: 0;
}

.data-table th,
.data-table td {
    padding: 8px 5px;
    text-align: center;
    border-bottom: 1px solid #e0e0e8;
    font-size: 14px;
    color: #737373;
}

.data-table th {
    background: #f8f8f8;
    padding: 10px 5px;
    font-weight: bold;
}

.data-table tr {
    height: 40px;
}

.data-table thead tr:first-child th {
    border-top: 1px solid #e0e0e8;
}

.data-table th:first-child,
.data-table td:first-child {
    border-left: 1px solid #e0e0e8;
}

.data-table th:last-child,
.data-table td:last-child {
    border-right: 1px solid #e0e0e8;
}

.data-table thead tr:first-child th:first-child {
    border-top-left-radius: 6px;
}

.data-table thead tr:first-child th:last-child {
    border-top-right-radius: 6px;
}

.data-table tbody tr:last-child td:first-child {
    border-bottom-left-radius: 6px;
}

.data-table tbody tr:last-child td:last-child {
    border-bottom-right-radius: 6px;
}

.data-table tbody tr:hover {
    background-color: rgba(240, 240, 245, 0.5);
}

.btn-outline {
    display: inline-block;
    padding: 4px 12px;
    background: transparent;
    color: #000;
    font-weight: bold;
    text-decoration: none;
    text-align: center;
    cursor: pointer;
}

.btn-outline:hover {
    text-decoration: underline;
}

.text-center {
    text-align: center;
}

/* レスポンシブ対応 */
@media screen and (max-width: 768px) {
    .container {
        width: 100%;
        padding: 5px;
    }
    
    .data-table th,
    .data-table td {
        padding: 8px 3px;
        font-size: 12px;
    }
}
</style>
@endsection
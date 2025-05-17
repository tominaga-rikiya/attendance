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
@endsection
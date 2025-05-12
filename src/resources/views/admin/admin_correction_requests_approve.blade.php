@extends('layouts.app')

@section('title','承認申請')

@section('css')
    <link rel="stylesheet" href="{{ asset('/css/show.css') }}">
@endsection

@section('content')
    @include('components.admin_header')
    
        <div class="container">
            <div class="date-navigation">
                <div class="date-title">
                    <h2 class="with-vertical-line">勤怠詳細</h2>
                </div>
        
        @if(isset($revisionRequest) && isset($attendance))
            <div class="form-container">
                <div class="form-group">
                    <label>名前</label>
                    <div class="input-wrapper">
                        <div class="readonly-box">
                            {{ $attendance->user->name }}
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>日付</label>
                    <div class="input-wrapper">
                        <div class="readonly-box time-display">
                            <span class="value-left">{{ \Carbon\Carbon::parse($attendance->date)->format('Y年') }}</span>
                            <span class="time-separator"></span>
                            <span class="value-right">{{ \Carbon\Carbon::parse($attendance->date)->format('n月j日') }}</span>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>出勤・退勤</label>
                    <div class="input-wrapper">
                        <div class="readonly-box time-display">
                            <span class="value-left">{{ $pendingRevision ? \Carbon\Carbon::parse($pendingRevision->new_start_time)->format('H:i') : \Carbon\Carbon::parse($attendance->start_time)->format('H:i') }}</span>
                            <span class="time-separator">～</span>
                            <span class="value-right">{{ $pendingRevision && $pendingRevision->new_end_time ? \Carbon\Carbon::parse($pendingRevision->new_end_time)->format('H:i') : ($attendance->end_time ? \Carbon\Carbon::parse($attendance->end_time)->format('H:i') : '') }}</span>
                        </div>
                    </div>
                </div>

                @if($pendingRevision && $pendingRevision->break_modifications)
                @php
                    $breakModifications = json_decode($pendingRevision->break_modifications, true);
                @endphp
                @if($breakModifications)
                    @foreach($breakModifications as $index => $break)
                        <div class="form-group">
                            <label>{{ $index == 0 ? '休憩' : '休憩' . ($index + 1) }}</label>
                            <div class="input-wrapper">
                                <div class="readonly-box time-display">
                                    <span class="value-left">{{ $break['start_time'] ? \Carbon\Carbon::parse($break['start_time'])->format('H:i') : '' }}</span>
                                    <span class="time-separator">～</span>
                                    <span class="value-right">{{ $break['end_time'] ? \Carbon\Carbon::parse($break['end_time'])->format('H:i') : '' }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    @foreach($attendance->breakTimes as $index => $break)
                        <div class="form-group">
                            <label>{{ $index == 0 ? '休憩' : '休憩' . ($index + 1) }}</label>
                            <div class="input-wrapper">
                                <div class="readonly-box time-display">
                                    <span class="value-left">{{ $break->start_time ? \Carbon\Carbon::parse($break->start_time)->format('H:i') : '' }}</span>
                                    <span class="time-separator">～</span>
                                    <span class="value-right">{{ $break->end_time ? \Carbon\Carbon::parse($break->end_time)->format('H:i') : '' }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
                @else
                @foreach($attendance->breakTimes as $index => $break)
                    <div class="form-group">
                        <label>{{ $index == 0 ? '休憩' : '休憩' . ($index + 1) }}</label>
                        <div class="input-wrapper">
                            <div class="readonly-box time-display">
                                <span class="value-left">{{ $break->start_time ? \Carbon\Carbon::parse($break->start_time)->format('H:i') : '' }}</span>
                                <span class="time-separator">～</span>
                                <span class="value-right">{{ $break->end_time ? \Carbon\Carbon::parse($break->end_time)->format('H:i') : '' }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
                @endif
                
                <div class="form-group">
                    <label>備考</label>
                    <div class="input-wrapper">
                        <div class="readonly-box">
                            {{ $pendingRevision ? $pendingRevision->note : '' }}
                        </div>
                    </div>
                </div>
            </div>
            @if($revisionRequest->status === 'approved')
                <div class="approval-status approved">
                    <p>承認済み</p>
                </div>
            @else
                <div class="button-container">
                    <form action="{{ route('admin.correction-requests.approve.post', $revisionRequest->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="approve-btn">承認</button>
                    </form>
                </div>
            @endif
        @else
            <div class="no-data">修正申請情報がありません</div>
        @endif
    </div>
@endsection
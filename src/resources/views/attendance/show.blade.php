@extends('layouts.app')

@section('title','勤怠詳細')

@section('css')
    <link rel="stylesheet" href="{{ asset('/css/show.css') }}">
@endsection

@section('content')
@include('components.header')
<div class="container">
    <div class="date-navigation">
        <div class="date-title">
            <h2 class="with-vertical-line">勤怠詳細</h2>
        </div>
        
        @if($pendingRevision)
        <!-- 読み取り専用表示 -->
        <div class="form-container">
            <div class="form-group">
                <label>名前</label>
                <div class="input-wrapper">
                    <div class="readonly-box">
                        {{ Auth::user()->name }}
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
        
        <div class="alert warning">
            *承認待ちのため修正はできません。
        </div>
        @else
            <!-- 修正可能フォーム -->
            <form id="attendance-form" action="{{ route('attendance.update', $attendance->id) }}" method="POST" novalidate>
                @csrf
                <div class="form-container">
                    <div class="form-group">
                        <label>名前</label>
                        <div class="input-wrapper">
                            <div class="readonly-box name-display">
                                {{ Auth::user()->name }}
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
                            <div class="time-inputs">
                                <input type="text" name="start_time" value="{{ old('start_time', \Carbon\Carbon::parse($attendance->start_time)->format('H:i')) }}" >
                                <span class="time-separator">～</span>
                                <input type="text" name="end_time" value="{{ old('end_time', $attendance->end_time ? \Carbon\Carbon::parse($attendance->end_time)->format('H:i') : '') }}" >
                            </div>
                            @if($errors->has('time_error'))
                                <div class="error-message">{{ $errors->first('time_error') }}</div>
                            @endif
                            @if($errors->has('start_time'))
                                <div class="error-message">{{ $errors->first('start_time') }}</div>
                            @endif
                            @if($errors->has('end_time'))
                                <div class="error-message">{{ $errors->first('end_time') }}</div>
                            @endif
                        </div>
                    </div>
                    
                    <!-- 既存の休憩時間を表示 -->
                    @foreach($attendance->breakTimes as $index => $break)
                    <div class="form-group">
                        <label>{{ $index == 0 ? '休憩' : '休憩' . ($index + 1) }}</label>
                        <div class="input-wrapper">
                            <div class="break-times-container">
                                <div class="break-time-row">
                                    <div class="time-inputs">
                                        <input type="text" name="breaks[{{ $index }}][start_time]" 
                                               value="{{ old("breaks.{$index}.start_time", $break->start_time ? \Carbon\Carbon::parse($break->start_time)->format('H:i') : '') }}">
                                        <span class="time-separator">～</span>
                                        <input type="text" name="breaks[{{ $index }}][end_time]" 
                                               value="{{ old("breaks.{$index}.end_time", $break->end_time ? \Carbon\Carbon::parse($break->end_time)->format('H:i') : '') }}">
                                    </div>
                                </div>
                            </div>
                            @if($errors->has("breaks.{$index}.start_time"))
                                <div class="error-message">{{ $errors->first("breaks.{$index}.start_time") }}</div>
                            @endif
                            @if($errors->has("breaks.{$index}.end_time"))
                                <div class="error-message">{{ $errors->first("breaks.{$index}.end_time") }}</div>
                            @endif
                            @if($errors->has('break_time_error'))
                                <div class="error-message">{{ $errors->first('break_time_error') }}</div>
                            @endif
                            @if($errors->has('break_pair_error'))
                                <div class="error-message">{{ $errors->first('break_pair_error') }}</div>
                            @endif
                            @if($errors->has('break_out_of_range'))
                                <div class="error-message">{{ $errors->first('break_out_of_range') }}</div>
                            @endif
                            @if($errors->has('break_overlap_error'))
                                <div class="error-message">{{ $errors->first('break_overlap_error') }}</div>
                            @endif
                        </div>
                    </div>
                    @endforeach

                    <!-- 新規追加用の休憩フィールド -->
                    @php
                    $newBreakIndex = $attendance->breakTimes->count();
                    $newBreakLabel = $newBreakIndex == 0 ? '休憩' : '休憩' . ($newBreakIndex + 1);
                    @endphp
                    <div class="form-group">
                    <label>{{ $newBreakLabel }}</label>
                    <div class="input-wrapper">
                        <div class="break-times-container">
                            <div class="break-time-row">
                                <div class="time-inputs">
                                    <input type="text" name="breaks[{{ $newBreakIndex }}][start_time]" 
                                           value="{{ old("breaks.{$newBreakIndex}.start_time", '') }}">
                                    <span class="time-separator">～</span>
                                    <input type="text" name="breaks[{{ $newBreakIndex }}][end_time]" 
                                           value="{{ old("breaks.{$newBreakIndex}.end_time", '') }}">
                                </div>
                            </div>
                        </div>
                        @if($errors->has("breaks.{$newBreakIndex}.start_time"))
                            <div class="error-message">{{ $errors->first("breaks.{$newBreakIndex}.start_time") }}</div>
                        @endif
                        @if($errors->has("breaks.{$newBreakIndex}.end_time"))
                            <div class="error-message">{{ $errors->first("breaks.{$newBreakIndex}.end_time") }}</div>
                        @endif
                        @if($errors->has('break_time_error'))
                            <div class="error-message">{{ $errors->first('break_time_error') }}</div>
                        @endif
                        @if($errors->has('break_pair_error'))
                            <div class="error-message">{{ $errors->first('break_pair_error') }}</div>
                        @endif
                        @if($errors->has('break_out_of_range'))
                            <div class="error-message">{{ $errors->first('break_out_of_range') }}</div>
                        @endif
                        @if($errors->has('break_overlap_error'))
                            <div class="error-message">{{ $errors->first('break_overlap_error') }}</div>
                        @endif
                    </div>
                    </div>

                    <div class="form-group">
                        <label>備考</label>
                        <div class="input-wrapper">
                            <input type="text" name="note" value="{{ old('note', $attendance->note ?? '') }}">
                            @error('note')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="button-container">
                        <button type="submit" class="btn-submit">修正</button>
                    </div>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
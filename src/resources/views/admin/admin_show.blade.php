@extends('layouts.app')  
@section('title','管理者詳細')  
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
            <form id="attendance-form" action="{{ route('admin.attendances.update', $attendance->id) }}" method="POST">
                @csrf
                <div class="form-container">
                    <div class="form-group">
                        <label>名前</label>
                        <div class="input-wrapper">
                            <div class="readonly-box">{{ $attendance->user->name }}</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>日付</label>
                        <div class="input-wrapper">
                            <div class="readonly-box">{{ \Carbon\Carbon::parse($attendance->date)->format('Y年 n月j日') }}</div>
                        </div>
                    </div>
                <!-- 出勤・退勤の部分 -->
<div class="form-group">
    <label>出勤・退勤</label>
    <div class="input-wrapper">
        <div class="time-inputs">
            <input type="text" name="start_time" value="{{ old('start_time', \Carbon\Carbon::parse($attendance->start_time)->format('H:i')) }}" required>
            <span class="time-separator">～</span>
            <input type="text" name="end_time" value="{{ $attendance->status == 'finished' && $attendance->end_time ? \Carbon\Carbon::parse($attendance->end_time)->format('H:i') : old('end_time', '') }}" required>
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
<!-- 休憩時間の部分 -->
<div class="form-group">
    <label>休憩</label>
    <div class="input-wrapper">
        <div class="break-times-container">
            @foreach($attendance->breakTimes as $i => $break)
                <div class="break-time-row">
                    <div class="time-inputs">
                        <input type="text" name="breaks[{{ $i }}][start_time]" value="{{ old("breaks.{$i}.start_time", $break->start_time ? \Carbon\Carbon::parse($break->start_time)->format('H:i') : '') }}">
                        <span class="time-separator">～</span>
                        <input type="text" name="breaks[{{ $i }}][end_time]" value="{{ old("breaks.{$i}.end_time", $break->end_time ? \Carbon\Carbon::parse($break->end_time)->format('H:i') : '') }}">
                    </div>
                    @if($errors->has("breaks.{$i}.start_time") || $errors->has("breaks.{$i}.end_time"))
                        <div class="error-message">
                            @if($errors->has("breaks.{$i}.start_time"))
                                {{ $errors->first("breaks.{$i}.start_time") }}
                            @elseif($errors->has("breaks.{$i}.end_time"))
                                {{ $errors->first("breaks.{$i}.end_time") }}
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        @if($errors->has('break_out_of_range'))
            <div class="error-message">{{ $errors->first('break_out_of_range') }}</div>
        @elseif($errors->has('break_pair_error'))
            <div class="error-message">{{ $errors->first('break_pair_error') }}</div>
        @elseif($errors->has('break_time_error'))
            <div class="error-message">{{ $errors->first('break_time_error') }}</div>
        @endif
    </div>
</div>

<!-- 備考欄の部分を修正 -->
<div class="form-group">
    <label>備考</label>
    <div class="input-wrapper">
        <input type="text" name="note" value="{{ old('note', $attendance->note ?? '') }}">
        @error('note')
            <div class="error-message">{{ $message }}</div>
        @enderror
    </div>
</div>
      
    </div>
      <div class="button-container" style="margin-top: 20px;">
            <button type="submit" class="btn-submit">修正</button>
        </div>
</form>
    </div>
</div>
@endsection

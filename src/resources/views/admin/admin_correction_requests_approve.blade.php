@extends('layouts.app')

@section('title','承認申請')

@section('css')
    <link rel="stylesheet" href="{{ asset('/css/index.css') }}">
@endsection

@section('content')
    @include('components.admin_header')
<div class="container">
    <h2 class="with-vertical-line">勤怠詳細</h2>
    
    @if(isset($revisionRequest) && isset($attendance))
        <div class="attendance-detail-table">
            <table>
                <tr>
                    <th>名前</th>
                    <td>{{ $attendance->user->name }}</td>
                </tr>
                <tr>
                    <th>日付</th>
                    <td>{{ \Carbon\Carbon::parse($attendance->date)->format('Y年 n月j日') }}</td>
                </tr>
                <tr>
                    <th>出勤・退勤</th>
                    <td>
                        {{ $pendingRevision->new_start_time ? \Carbon\Carbon::parse($pendingRevision->new_start_time)->format('H:i') : '-' }} ～ 
                        {{ $pendingRevision->new_end_time ? \Carbon\Carbon::parse($pendingRevision->new_end_time)->format('H:i') : '-' }}
                    </td>
                </tr>
                <tr>
                    <th>休憩</th>
                    <td>
                        @if($pendingRevision->break_modifications)
                            @php
                                $breakModifications = json_decode($pendingRevision->break_modifications, true);
                            @endphp
                            @foreach($breakModifications as $break)
                                <div class="break-time-item">
                                    {{ isset($break['start_time']) ? \Carbon\Carbon::parse($break['start_time'])->format('H:i') : '' }} ～ 
                                    {{ isset($break['end_time']) ? \Carbon\Carbon::parse($break['end_time'])->format('H:i') : '' }}
                                </div>
                            @endforeach
                        @else
                            @foreach($attendance->breakTimes as $break)
                                <div class="break-time-item">
                                    {{ $break->start_time ? \Carbon\Carbon::parse($break->start_time)->format('H:i') : '' }} ～ 
                                    {{ $break->end_time ? \Carbon\Carbon::parse($break->end_time)->format('H:i') : '' }}
                                </div>
                            @endforeach
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>備考</th>
                    <td>{{ $pendingRevision->note }}</td>
                </tr>
            </table>
        </div>
        
        @if($revisionRequest->status === 'approved')
            <div class="approval-status approved">
                <p>承認済み</p>
            </div>
        @else
            <div class="button-container">
                <form action="{{ route('admin.correction-requests.approve.post', $revisionRequest->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="approve-btn">承認する</button>
                </form>
            </div>
        @endif
    @else
        <div class="no-data">修正申請情報がありません</div>
    @endif
</div>
@endsection


    <style>
        /* 全体のレイアウト */
        .container {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            margin-top: 40px;
        }

        /* タイトル部分 */
        .with-vertical-line {
            border-left: 4px solid #333;
            padding-left: 10px;
            color: #000000;
            font-size: 24px;
            font-weight: bold;
            line-height: 1.2;
            margin-bottom: 30px;
            margin-top: 5px;
        }

        /* テーブルスタイル */
        .attendance-detail-table {
            width: 100%;
            max-width: 900px;
            border: 1px solid #e1e1e1;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-top: 0;
        }

        .attendance-detail-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .attendance-detail-table th, 
        .attendance-detail-table td {
            padding: 15px 0;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }

        .attendance-detail-table th {
            width: 120px;
            color: #737373;
            font-weight: bold;
            vertical-align: top;
        }

        .attendance-detail-table td {
            font-weight: bold;
        }

        /* 承認ステータス */
        .approval-status {
            width: 100%;
            max-width: 900px;
            text-align: right;
            padding: 15px 0;
            font-weight: bold;
            margin-top: 10px;
        }

        .approval-status.approved {
            color: #38c172;
        }

        /* 承認ボタン */
        .approve-btn {
            padding: 15px;
            width: 130px;
            background-color: #000;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
        }

        .approve-btn:hover {
            background-color: #333;
        }

        /* 休憩時間表示 */
        .break-time-item {
            margin-bottom: 5px;
        }
        
        /* ボタンコンテナ */
        .button-container {
            width: 100%;
            max-width: 900px;
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }
    </style>
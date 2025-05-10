@extends('layouts.app')

@section('title','申請一覧')

@section('css')
    <link rel="stylesheet" href="{{ asset('/css/correction_index.css') }}">
@endsection

@section('content')
@include('components.header')
<div class="container">
   <div class="date-navigation">
        <div class="date-title">
            <h2 class="with-vertical-line">申請一覧</h2>
        </div>
    </div>
    

    <div class="tab-header">
        <a href="#" class="tab-link active" data-tab="pending">承認待ち</a>
        <a href="#" class="tab-link" data-tab="approved">承認済み</a>
    </div>

    <div class="tab-content active" id="pending-content">
        <table class="list-table">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                    @if(auth()->user()->isAdmin())
                    <th>操作</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($pendingRequests as $request)
                <tr>
                    <td>承認待ち</td>
                    <td>{{ $request->user->name }}</td>
                    <td>{{ \Carbon\Carbon::parse($request->attendance->date)->format('Y/m/d') }}</td>
                    <td class="reason-cell">{{ $request->note }}</td>
                    <td>
                        @if($request->created_at)
                            {{ $request->created_at instanceof \Carbon\Carbon 
                                ? $request->created_at->format('Y/m/d') 
                                : date('Y/m/d', strtotime($request->created_at)) }}
                        @else
                            -
                        @endif
                    </td>
                    <td>
                       <a href="{{ route('attendance.show', $request->attendance_id) }}" class="detail-btn">詳細</a>
                    </td>
                    @if(auth()->user()->isAdmin())
                    <td>
                        <form action="{{ route('revision.approve', $request->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="approve-btn">承認する</button>
                        </form>
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="{{ auth()->user()->isAdmin() ? 7 : 6 }}" class="no-data">承認待ちの申請はありません</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="tab-content" id="approved-content">
        <table class="list-table">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>承認日時</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse($approvedRequests as $request)
                <tr>
                    <td>承認済み</td>
                    <td>{{ $request->user->name }}</td>
                    <td>{{ \Carbon\Carbon::parse($request->attendance->date)->format('Y/m/d') }}</td>
                    <td class="reason-cell">{{ $request->note }}</td>
                    <td>{{ \Carbon\Carbon::parse($request->approved_at)->format('Y/m/d') }}</td>
                    <td>
                       <a href="{{ route('attendance.show', $request->attendance_id) }}" class="detail-btn">詳細</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="no-data">承認済みの申請はありません</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // タブ切り替え機能
        const tabLinks = document.querySelectorAll('.tab-link');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // アクティブクラスを全て削除
                tabLinks.forEach(lnk => lnk.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                
                // クリックされたタブをアクティブに
                this.classList.add('active');
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId + '-content').classList.add('active');
            });
        });
    });
</script>
@endsection
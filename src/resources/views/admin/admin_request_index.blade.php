@extends('layouts.app')

@section('title','管理者申請一覧')

@section('css')
    <link rel="stylesheet" href="{{ asset('/css/correction_index.css') }}">
@endsection

@section('content')
@include('components.admin_header')
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
                </tr>
            </thead>
            <tbody>
                @forelse($pendingRequests as $request)
                <tr>
                    <td>承認待ち</td>
                    <td>{{ $request->user->name }}</td>
                    <td>
                        @if($request->attendance)
                            {{ $request->attendance->formatted_date }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="reason-cell">{{ $request->note }}</td>
                    <td>
                        @if($request->created_at)
                            {{ \Carbon\Carbon::parse($request->created_at)->format('Y/m/d') }}
                        @else
                            -
                        @endif
                    </td>
                    <td>
                      <a href="{{ route('admin.correction-requests.show', $request->id) }}" class="detail-btn">詳細</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="no-data">承認待ちの申請はありません</td>
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
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse($approvedRequests as $request)
                <tr>
                    <td>承認済み</td>
                    <td>{{ $request->user->name }}</td>
                    <td>
                        @if($request->attendance)
                            {{ $request->attendance->formatted_date }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="reason-cell">{{ $request->note }}</td>
                    <td>
                        @if($request->created_at)
                            {{ \Carbon\Carbon::parse($request->created_at)->format('Y/m/d') }}
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.correction-requests.show', $request->id) }}" class="detail-btn">詳細</a>
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
        const tabLinks = document.querySelectorAll('.tab-link');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                tabLinks.forEach(lnk => lnk.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                
                this.classList.add('active');
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId + '-content').classList.add('active');
            });
        });
    });
</script>
@endsection
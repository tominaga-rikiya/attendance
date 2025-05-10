@extends('layouts.app')

@section('title','スタッフ一覧')

@section('css')
    <link rel="stylesheet" href="{{ asset('/css/index.css') }}">
@endsection

@section('content')
    @include('components.admin_header')

         <div class="date-title">
            <h2 class="with-vertical-line">スタッフ一覧</h2>
        </div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>名前</th>
                                <th>メールアドレス</th>
                                <th>月次勤怠</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    <a href="{{ route('staff.attendance', $user->id) }}" class="btn btn-sm btn-primary">詳細</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
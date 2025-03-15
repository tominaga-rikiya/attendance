@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/verify.css') }}">
@endsection

@section('content')
@include('components.header')
<div class="container">
    <div class="message">
        登録していただいたメールアドレスに認証メールを送付しました。<br>
        メール認証を完了してください。
    </div>

    @if (session('resent'))
        <div class="alert alert-success" role="alert">
            認証メールを再送信しました。メールをご確認ください。
        </div>
    @endif

    <form class="mail_resend--form" method="POST" action="{{ route('verification.verify-redirect') }}">
        @csrf
        <button type="submit" class="verify-button">認証はこちらから</button>
    </form>

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="resend-link">認証メールを再送する</button>
    </form>
</div>
@endsection
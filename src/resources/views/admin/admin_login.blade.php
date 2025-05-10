@extends('layouts.app')

@section('title','管理者ログイン')

@section('css')
    <link rel="stylesheet" href="{{ asset('/css/auth.css') }}">
@endsection

@section('content')
    @include('components.admin_header')
    <form action="/admin/login" method="post" class="auth center">
        @csrf
        <h1 class="page__title">管理者ログイン</h1>
        <input type="hidden" name="admin" value="1">

        
        <label for="mail" class="entry__name">メールアドレス</label>
        <input name="email" id="mail" type="email" class="input" value="{{ old('email') }}">
        <div class="form__error">
            @error('email')
                {{ $message }}
            @enderror
        </div>
        
        <label for="password" class="entry__name">パスワード</label>
        <input name="password" id="password" type="password" class="input">
        <div class="form__error">
            @error('password')
                {{ $message }}
            @enderror
        </div>
        
        <button class="auth-form__btn btn">管理者ログインする</button>
    </form>
@endsection
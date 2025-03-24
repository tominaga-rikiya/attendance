@extends('layouts.app')

@section('title', '会員登録')

@section('css')
    <link rel="stylesheet" href="{{ asset('/css/auth.css') }}">
@endsection

@section('content')
    @include('components.header')
    
    <form action="/register" method="POST" class="auth center">
        @csrf
        <h1 class="page__title">会員登録</h1>
        
        <label for="name" class="entry__name">名前</label>
        <input name="name" id="name" type="text" class="input" value="{{ old('name') }}">
        <div class="form__error">
            @error('name')
                {{ $message }}
            @enderror
        </div>
        
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
        
        <label for="password_confirm" class="entry__name">パスワード確認</label>
        <input name="password_confirmation" id="password_confirm" type="password" class="input">
        <div class="form__error">
            @error('password_confirmation')
                {{ $message }}
            @enderror
        </div>
        
        <button class="auth-form__btn btn" type="submit">登録する</button>
        <a href="{{ route('login') }}" class="link">ログインはこちら</a>
    </form>
@endsection
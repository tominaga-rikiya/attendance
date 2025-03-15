@extends('layouts.app')

<<<<<<< HEAD
@section('title', '会員登録')

@section('css')
    <link rel="stylesheet" href="{{ asset('/css/register.css') }}">
@endsection

@section('content')
    @include('componensts.header')
    
    <form action="/register" method="POST" class="auth center">
        @csrf
        <h1 class="page_title">会員登録</h1>
        
        <label for="name" class="entry_name">名前</label>
        <input name="name" id="name" type="text" class="input" value="{{ old('name') }}">
        <div class="from_error">
            @error('name')
                {{ $message }}
            @enderror
        </div>
        
        <label for="mail" class="entry_name">メールアドレス</label>
        <input name="email" id="mail" type="email" class="input" value="{{ old('email') }}">
        <div class="from_error">
            @error('email')
                {{ $message }}
            @enderror
        </div>
        
        <label for="password" class="entry_name">パスワード</label>
        <input name="password" id="password" type="password" class="input">
        <div class="from_error">
            @error('password')
                {{ $message }}
            @enderror
        </div>
        
        <label for="password_confirm" class="entry_name">確認用パスワード</label>
        <input name="password_confirmation" id="password_confirm" type="password" class="input">
        <div class="from_error">
            @error('password_confirmation')
                {{ $message }}
            @enderror
        </div>
        
        <button class="register-form__btn btn" type="submit">登録する</button>
        <a href="/login" class="link">ログインはこちら</a>
    </form>
=======
@section('title','会員登録')

@section('css')
<link rel="stylesheet" href="{{asset('/css/register.css') }}">
@endsection

@section('content')

@include('componensts.header')
<form action="/register" method="POST" class="auth center">
@csrf
<h1 class="page_title">会員登録</h1>
<label for="name" class="entry_name">名前</label>
<input name="name" id="name" type="text" class="input" value="{{old('name') }}">
<div class="from_error">
    @error('name')
    {{$message}}
    @enderror
</div>
<label for="mail" class="entry_name">メールアドレス</label>
<input name="email" id="mail" type="email" class="input" value="{{old('email') }}">
<div class="from_error">
    @error('email')
    {{$message}}
    @enderror
</div>
<label for="passwoord" class="entry_name">パスワード</label>
<input name="password" id="password" type="password" class="input" value="{{old('password') }}">
<div class="from_error">
    @error('password')
    {{$message}}
    @enderror
</div>
<label for="passwoord_confiem" class="entry_name">確認用パスワード</label>
<input name="passwoord_confiem" id="passwoord_confiem" type="passwoord_confiem" class="input">
<button class="btn btn--big">登録する</button>
<a href="/login" class="link">ログインはこちら</a>
</form>
>>>>>>> origin/main
@endsection
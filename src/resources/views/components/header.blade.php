<header class="header">
    <div class="header__logo">
        <a href="/"><img src="{{ asset('img/logo.png') }}" alt="ロゴ"></a>
    </div>
    @if( !in_array(Route::currentRouteName(), ['register', 'login', 'verification.notice']) )
        <nav class="header__nav">
            <ul>
                @if(Auth::check())
                    <li>
                        <form action="/logout" method="post">
                            @csrf
                            <button class="header__logout">ログアウト</button>
                        </form>
                    </li>
                    <li><a href="/attendance">勤怠</a></li>
                @else
                    <li><a href="/login">勤怠一覧</a></li>
                    <li><a href="/register">申請</a></li>
                @endif
            </ul>
        </nav>
    @endif
</header>
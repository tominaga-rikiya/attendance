<header class="header">
    <div class="header__logo">
        <a href="/"><img src="{{ asset('img/logo.png') }}" alt="ロゴ"></a>
    </div>
    @if( !in_array(Route::currentRouteName(), ['register', 'login', 'verification.notice']) )
        <nav class="header__nav">
            <ul>
                @if(auth()->user())
                     <li><a href="/attendance">勤怠</a></li>
                     <li><a href="/attendances">勤怠一覧</a></li>
                    <li><a href="/correction-requests">申請</a></li>
                    <li>
                        <form action="{{ route('logout') }}" method="post">
                            @csrf
                            <button type="submit" class="header__logout">ログアウト</button>
                        </form>
                    </li>
                @else
                   
                @endif
            </ul>
        </nav>
    @endif
</header>
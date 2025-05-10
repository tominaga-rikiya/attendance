<header class="header">     
    <div class="header__logo">         
        <a href="/"><img src="{{ asset('img/logo.png') }}" alt="ロゴ"></a>     
    </div>     
    @if(!in_array(Route::currentRouteName(), ['register', 'login', 'verification.notice']))         
        <nav class="header__nav">             
            <ul>                 
                @if(auth()->user() && auth()->user()->isAdmin())                      
                    <li><a href="{{ route('admin.attendances.index') }}">勤怠一覧</a></li>                      
                    <li><a href="{{ route('staff.index') }}">スタッフ一覧</a></li>                     
                    <li><a href="{{ route('admin.correction-requests.index') }}">申請一覧</a></li>                     
                    <li>                         
                        <form action="{{ route('logout') }}" method="post">                             
                            @csrf                             
                            <button type="submit" class="header__logout">ログアウト</button>                         
                        </form>                     
                    </li>                
                @endif             
            </ul>         
        </nav>     
    @endif 
</header>
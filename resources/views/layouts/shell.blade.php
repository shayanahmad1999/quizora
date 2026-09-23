@guest
    {!! $content !!}
@else
<div class="workspace">
    <aside class="sidebar">
        <a href="{{ route('dashboard') }}" class="brand" data-nav><span class="brand-mark">q.</span><span>{{ $settings->site_name }}<small>LEARNING WORKSPACE</small></span></a>
        <div class="nav-caption">{{ auth()->user()->isAdmin() ? 'WORKSPACE' : 'YOUR LEARNING' }}</div>
        <nav class="nav-list" aria-label="Main navigation">
            @php
                $links = auth()->user()->isAdmin() ? [
                    ['dashboard','Overview','grid','dashboard'], ['admin.categories.index','Categories','layers','admin.categories.*'],
                    ['admin.questions.index','Question bank','book','admin.questions.*'], ['admin.quizzes.index','Quiz studio','spark','admin.quizzes.*'],
                    ['admin.users.index','People','users','admin.users.*'], ['admin.themes.index','Appearance','palette','admin.themes.*'],
                    ['admin.reports.index','Reports','chart','admin.reports.*'], ['admin.audit.index','Activity log','clock','admin.audit.*'],
                    ['admin.settings.edit','Settings','settings','admin.settings.*'],
                ] : [
                    ['dashboard','My learning','grid','dashboard'], ['quizzes.index','Explore quizzes','book','quizzes.*'],
                    ['attempts.index','My results','chart','attempts.*'], ['profile.edit','My account','users','profile.*'],
                ];
            @endphp
            @foreach($links as [$route,$label,$icon,$match])
                <a href="{{ route($route) }}" data-nav @class(['nav-item','is-active'=>request()->routeIs($match)]) @if(request()->routeIs($match)) aria-current="page" @endif><x-icon :name="$icon"/><span>{{ $label }}</span></a>
            @endforeach
        </nav>
        <div class="sidebar-bottom">
            <div class="sidebar-note"><x-icon name="spark"/><strong>{{ auth()->user()->isAdmin() ? 'Make room for progress.' : 'One question at a time.' }}</strong><p>{{ auth()->user()->isAdmin() ? 'Build thoughtful assessments. Help every learner move forward.' : 'Small steps today build confidence for tomorrow.' }}</p></div>
            <form method="post" action="{{ route('logout') }}" data-ajax data-confirm="Sign out of your account?"><input type="hidden" name="_token" value="{{ csrf_token() }}"><button class="nav-item logout" type="submit"><x-icon name="logout"/><span>Sign out</span></button></form>
        </div>
    </aside>
    <div class="workspace-body">
        <header class="topbar">
            <div class="breadcrumb-line">Workspace <span>/</span> <strong>{{ $title }}</strong></div>
            <div class="topbar-right"><span class="theme-chip"><i></i>{{ $theme['name'] }}</span><a class="profile-link" data-nav href="{{ route('profile.edit') }}"><span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name,0,1)) }}</span><span><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->isAdmin() ? 'Super administrator' : 'Learner' }}</small></span></a></div>
        </header>
        <main class="page-content" data-page tabindex="-1">
            @if(session('status'))<div class="notice success">{{ session('status') }}</div>@endif
            {!! $content !!}
            <footer class="page-footer"><span>{{ $settings->site_name }} &middot; Learn with intention.</span><span>{{ now()->timezone(config('app.display_timezone'))->format('d M Y') }}</span></footer>
        </main>
    </div>
</div>
@endguest

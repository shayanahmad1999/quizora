<div class="login-layout">
    <section class="login-story">
        <a class="brand" href="{{ route('login') }}" data-nav><span class="brand-mark">q.</span><span>{{ $settings->site_name }}<small>THE LEARNING WORKSPACE</small></span></a>
        <div class="login-story-content"><span class="eyebrow light">CURIOSITY LOOKS GOOD ON YOU</span><h1>A little practice.<br>A lot of<br><em>possibility.</em></h1><p>Explore ideas. Test your understanding.<br>Take the next step in your learning journey.</p>
        <div class="story-tags"><span>C &amp; C++</span><span>Sciences</span><span>English</span><span>And beyond</span></div></div>
        <div class="login-story-footer"><span class="tiny-dot"></span> {{ $settings->tagline }}</div>
        <div class="orbit orbit-one" aria-hidden="true"></div><div class="orbit orbit-two" aria-hidden="true"></div>
    </section>
    <section class="login-panel">
        <div class="login-form-wrap"><span class="eyebrow">YOUR NEXT CHAPTER</span><h2>Welcome back.</h2><p class="muted">Sign in with the account provided by your administrator.</p>
        <form action="{{ route('login.store') }}" method="post" data-ajax class="login-form">
            @csrf <x-errors/>
            <x-field name="email" label="Email address" type="email" placeholder="you@example.com" autocomplete="username" :required="true" maxlength="190"/>
            <x-field name="password" label="Password" type="password" placeholder="Enter your password" autocomplete="current-password" :required="true" maxlength="128"/>
            <button class="btn btn-primary btn-large full-width" type="submit">Sign in to your workspace <x-icon name="arrow"/></button>
        </form>
        <div class="login-help"><strong>Need an account or a password reset?</strong><p>Please contact your workspace administrator.@if($settings->support_email) <span>{{ $settings->support_email }}</span>@endif</p></div>
        <div class="secure-note"><x-icon name="check"/> Your learning. Your pace. Your space.</div>
        </div>
    </section>
</div>

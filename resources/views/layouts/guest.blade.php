<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $org->name }} — {{ t('auth.signIn') }}</title>
    {{ Vite::fonts() }}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-brand" aria-label="{{ $org->name }}">
            <div class="auth-grid" aria-hidden="true"></div>
            <a class="auth-brand-name" href="{{ route('login') }}">
                <span class="auth-monogram">{{ initials($org->name) }}</span>
                <span><strong>{{ $org->name }}</strong><small>{{ t('app.tagline') }}</small></span>
            </a>
            <div class="auth-story">
                <span class="auth-brand-tag"><span></span> {{ t('auth.together') }}</span>
                <h2>{{ t('auth.growTogether') }}</h2>
                <p>{{ t('auth.groupFuture') }}</p>
                <div class="auth-art" aria-hidden="true">
                    <div class="auth-orbit auth-orbit-one"></div><div class="auth-orbit auth-orbit-two"></div>
                    <div class="auth-art-card">
                        <span class="auth-art-icon"><x-heroicon-o-user-group /></span>
                        <div class="auth-art-line"></div><div class="auth-art-line short"></div>
                        <div class="auth-bars"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
                    </div>
                    <span class="auth-float auth-float-top"><x-heroicon-o-sparkles /></span>
                    <span class="auth-float auth-float-bottom"><x-heroicon-o-shield-check /></span>
                </div>
            </div>
            <div class="auth-brand-footer"><span>{{ t('nav.savings') }}</span><i></i><span>{{ t('nav.shares') }}</span><i></i><span>{{ t('nav.loans') }}</span></div>
        </section>
        <section class="auth-main">
            <div class="auth-topbar"><span class="auth-mobile-brand">{{ $org->name }}</span><x-lang-toggle /></div>
            <div class="auth-card">@yield('content')</div>
            <footer class="auth-footer">&copy; {{ now()->year }} {{ $org->name }}<span>{{ t('auth.memberPortal') }}</span></footer>
        </section>
    </main>
    <x-toast-host />
</body>
</html>

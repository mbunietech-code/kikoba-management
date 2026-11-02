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
            <a class="auth-brand-name" href="{{ route('login') }}">
                <span class="auth-monogram">{{ initials($org->name) }}</span>
                <span><strong>{{ $org->name }}</strong><small>{{ t('app.tagline') }}</small></span>
            </a>
            <div class="auth-story">
                <h2>{{ t('auth.growTogether') }}</h2>
                <p>{{ t('auth.groupFuture') }}</p>
                <ul class="auth-points">
                    <li><x-heroicon-o-check-circle aria-hidden="true" />{{ t('auth.pointBalances') }}</li>
                    <li><x-heroicon-o-check-circle aria-hidden="true" />{{ t('auth.pointLoans') }}</li>
                    <li><x-heroicon-o-check-circle aria-hidden="true" />{{ t('auth.pointStatements') }}</li>
                </ul>
            </div>
            <p class="auth-brand-footer">&copy; {{ now()->year }} {{ $org->name }}</p>
        </section>
        <section class="auth-main">
            <div class="auth-topbar"><span class="auth-mobile-brand">{{ $org->name }}</span><x-lang-toggle /></div>
            <div class="auth-card">@yield('content')</div>
            <footer class="auth-footer"><span>{{ t('auth.keepPrivate') }}</span></footer>
        </section>
    </main>
    <x-toast-host />
</body>
</html>

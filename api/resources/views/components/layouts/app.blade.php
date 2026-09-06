@props(['title' => null])
@php
    use App\Support\Nav;
    $isStaff = auth()->user()?->isStaff();
    $sections = $isStaff ? Nav::admin() : Nav::member();
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ ($title ?? '') ? $title.' · ' : '' }}{{ $org->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-neutral-100">
<div x-data="{ nav: false }" class="flex min-h-screen">

    {{-- sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full overflow-y-auto border-r border-neutral-200 bg-white transition-transform lg:static lg:translate-x-0"
           :class="nav && '!translate-x-0'">
        @include('partials.sidebar', ['sections' => $sections])
    </aside>
    <div x-show="nav" x-cloak class="fixed inset-0 z-30 bg-neutral-900/30 lg:hidden" x-on:click="nav = false"></div>

    <div class="flex min-w-0 flex-1 flex-col">
        {{-- topbar --}}
        <header class="sticky top-0 z-20 flex h-14 items-center gap-3 border-b border-neutral-200 bg-white px-4">
            <button class="text-neutral-500 lg:hidden" x-on:click="nav = true"><x-heroicon-o-bars-3 class="h-5 w-5" /></button>
            <div class="relative hidden max-w-xs flex-1 sm:block">
                <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400" />
                <input placeholder="{{ t('common.search') }}…" class="h-9 w-full rounded-lg border border-neutral-200 bg-neutral-50 pl-9 pr-3 text-sm">
            </div>
            <div class="ml-auto flex items-center gap-2">
                <x-lang-toggle />
                <a href="{{ $isStaff ? route('admin.notifications.index') : route('member.notifications') }}" class="relative text-neutral-400 hover:text-neutral-600">
                    <x-heroicon-o-bell class="h-5 w-5" />
                </a>
                <div x-data="{ open: false }" class="relative">
                    <button x-on:click="open = !open" x-on:click.outside="open = false" class="flex items-center gap-2">
                        <x-avatar :name="$currentUser?->name ?? 'U'" color="#0f172a" size="sm" />
                    </button>
                    <div x-show="open" x-transition x-cloak class="absolute right-0 z-30 mt-2 w-52 rounded-xl border border-neutral-200 bg-white py-1 shadow-[var(--shadow-pop)]">
                        <div class="border-b border-neutral-100 px-3 py-2">
                            <p class="text-[13px] font-semibold text-neutral-900">{{ $currentUser?->name }}</p>
                            <p class="text-[11px] text-neutral-400">{{ t('users.roles.'.($currentUser?->primaryRole() ?? 'member')) }}</p>
                        </div>
                        <a href="{{ $isStaff ? route('admin.settings.index') : route('member.profile') }}" class="block px-3 py-2 text-[13px] text-neutral-700 hover:bg-neutral-50">{{ t('common.profile') }}</a>
                        <form method="POST" action="{{ route('logout') }}">@csrf
                            <button class="block w-full px-3 py-2 text-left text-[13px] text-red-600 hover:bg-red-50">{{ t('common.logout') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl flex-1 p-4 sm:p-6">
            {{ $slot }}
        </main>
    </div>
</div>

<x-toast-host />
</body>
</html>

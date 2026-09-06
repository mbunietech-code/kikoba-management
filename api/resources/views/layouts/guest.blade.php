<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $org->name }} — {{ t('app.tagline') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-neutral-100">
    <div class="flex min-h-screen">
        {{-- brand panel --}}
        <div class="relative hidden w-[46%] overflow-hidden bg-primary-900 lg:block">
            <div class="absolute inset-0" style="background:radial-gradient(circle at 20% 20%,rgba(45,212,191,.25),transparent 45%),radial-gradient(circle at 80% 80%,rgba(37,99,235,.25),transparent 45%)"></div>
            <div class="relative flex h-full flex-col justify-between p-12 text-white">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/15 font-display text-base font-bold backdrop-blur">{{ initials($org->name) }}</span>
                    <div>
                        <p class="font-display text-lg font-bold">{{ $org->name }}</p>
                        <p class="text-sm text-white/60">{{ t('app.tagline') }}</p>
                    </div>
                </div>
                <div>
                    <h2 class="max-w-md font-display text-3xl font-bold leading-tight">One platform for members, shares, savings, loans, projects &amp; protection.</h2>
                    <p class="mt-4 max-w-md text-white/70">Every shilling in or out carries a source, a reference, an accounting record and an audit trail.</p>
                </div>
                <div class="grid grid-cols-3 gap-4 text-sm">
                    @foreach ([['Double-entry','accounting'],['Audit trail','on every action'],['Multi-org','ready']] as $f)
                        <div class="rounded-xl bg-white/10 p-3">
                            <p class="font-semibold">{{ $f[0] }}</p>
                            <p class="text-white/50">{{ $f[1] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- form panel --}}
        <div class="flex flex-1 items-center justify-center p-6">
            <div class="w-full max-w-sm">
                <div class="mb-4 flex justify-end"><x-lang-toggle /></div>
                @yield('content')
            </div>
        </div>
    </div>

    <x-toast-host />
</body>
</html>

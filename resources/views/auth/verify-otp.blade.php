@extends('layouts.guest')
@section('content')
    <h1 class="font-display text-2xl font-bold text-neutral-900">{{ t('auth.otpTitle') }}</h1>
    <p class="mt-1.5 text-sm text-neutral-500">{{ t('auth.otpSubtitle', ['target' => $destination]) }}</p>
    @if ($debug)
        <p class="mt-2 rounded-lg bg-amber-50 px-3 py-1.5 text-[12px] text-amber-700">Dev code: <span class="font-mono font-semibold">{{ $debug }}</span></p>
    @endif
    <form method="POST" action="{{ route('verify-otp') }}" class="mt-6">
        @csrf
        <x-field name="code">
            <x-input name="code" inputmode="numeric" maxlength="6" class="text-center font-display text-xl font-bold tracking-[0.5em]" placeholder="••••••" />
        </x-field>
        <button class="k-btn k-btn-primary mt-4 h-11 w-full">{{ t('auth.otpVerify') }}</button>
    </form>
    <a href="{{ route('login') }}" class="mt-4 block text-center text-[13px] font-medium text-primary-700 hover:underline">{{ t('auth.backToSignIn') }}</a>
@endsection

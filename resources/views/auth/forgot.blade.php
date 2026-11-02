@extends('layouts.guest')
@section('content')
    <h1 class="auth-title">{{ t('auth.forgotTitle') }}</h1>
    <p class="auth-subtitle">{{ t('auth.forgotHowTo') }}</p>
    <ol class="mt-5 list-decimal space-y-2 pl-5 text-[13.5px] leading-relaxed text-neutral-700">
        <li>{{ t('auth.forgotStep1') }}</li>
        <li>{{ t('auth.forgotStep2') }}</li>
        <li>{{ t('auth.forgotStep3') }}</li>
    </ol>
    <a href="{{ route('login') }}" class="k-btn k-btn-outlined mt-6 w-full">{{ t('auth.backToSignIn') }}</a>
@endsection

@extends('layouts.guest')
@section('content')
    <h1 class="auth-title">{{ t('account.chooseYourPassword') }}</h1>
    <p class="auth-subtitle">{{ t('account.chooseYourPasswordHint') }}</p>
    <div class="mt-6">@include('partials.password-form')</div>
    <form method="POST" action="{{ route('logout') }}" class="mt-4">@csrf
        <button class="text-[13px] font-medium text-neutral-500 hover:text-neutral-800">{{ t('common.logout') }}</button>
    </form>
@endsection

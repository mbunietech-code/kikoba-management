@extends('layouts.guest')
@section('content')
    <h1 class="font-display text-2xl font-bold text-neutral-900">{{ t('auth.forgotTitle') }}</h1>
    <p class="mt-1.5 text-sm text-neutral-500">{{ t('auth.forgotSubtitle') }}</p>
    <form method="POST" action="{{ route('forgot') }}" class="mt-6 flex flex-col gap-4">
        @csrf
        <x-field :label="t('common.email')" name="email"><x-input type="email" name="email" /></x-field>
        <button class="k-btn k-btn-primary h-11">{{ t('auth.sendResetLink') }}</button>
    </form>
    <a href="{{ route('login') }}" class="mt-4 block text-center text-[13px] font-medium text-primary-700 hover:underline">{{ t('auth.backToSignIn') }}</a>
    @if (session('status'))<p class="mt-3 rounded-lg bg-tertiary-50 px-3 py-2 text-[13px] text-tertiary-700">{{ session('status') }}</p>@endif
@endsection

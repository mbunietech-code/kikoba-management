@extends('layouts.guest')

@section('content')
    <div x-data="{ login: @js(old('login', 'admin@kikoba.co.tz')), password: 'demo1234' }">
        <h1 class="font-display text-2xl font-bold text-neutral-900">{{ t('auth.signIn') }}</h1>

        <form method="POST" action="{{ route('login') }}" class="mt-6 flex flex-col gap-4">
            @csrf
            <x-field :label="t('auth.emailOrPhone')" name="login">
                <x-input name="login" x-model="login" autocomplete="username" />
            </x-field>
            <x-field :label="t('auth.password')" name="password">
                <x-input type="password" name="password" x-model="password" autocomplete="current-password" />
            </x-field>

            <div class="flex items-center justify-between text-[13px]">
                <label class="flex items-center gap-2 text-neutral-600">
                    <input type="checkbox" name="remember" checked class="h-4 w-4 rounded border-neutral-300 text-primary-600"> {{ t('auth.rememberMe') }}
                </label>
                <a href="{{ route('forgot') }}" class="font-medium text-primary-700 hover:underline">{{ t('auth.forgotPassword') }}</a>
            </div>

            <button class="k-btn k-btn-primary h-11 text-[15px]">{{ t('auth.signIn') }} →</button>
        </form>

        <div class="mt-5 rounded-xl bg-neutral-100 p-3">
            <p class="mb-2 text-center text-[12px] text-neutral-500">{{ t('auth.demoAccounts') }} <code class="font-mono">demo1234</code></p>
            <div class="flex flex-wrap justify-center gap-1.5">
                @foreach (['admin@kikoba.co.tz' => 'admin', 'treasurer@kikoba.co.tz' => 'treasurer', 'officer@kikoba.co.tz' => 'loan_officer', 'member@mfano.co.tz' => 'member'] as $email => $role)
                    <button type="button" x-on:click="login = '{{ $email }}'; password = 'demo1234'"
                            class="rounded-lg bg-white px-2.5 py-1 text-[12px] font-medium text-neutral-600 ring-1 ring-neutral-200 hover:ring-primary-300">
                        {{ t("users.roles.$role") }}
                    </button>
                @endforeach
            </div>
        </div>

        <p class="mt-4 text-center text-[13px] text-neutral-500">
            {{ t('auth.noAccount') }} <span class="font-medium text-neutral-700">{{ t('auth.contactAdmin') }}</span>
        </p>
    </div>
@endsection

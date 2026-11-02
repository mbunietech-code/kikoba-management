@extends('layouts.guest')

@section('content')
    <div x-data="{ showPassword: false, submitting: false }">
        <h1 class="auth-title">{{ t('auth.welcomeBack') }}</h1>
        <p class="auth-subtitle">{{ t('auth.signInSubtitle') }}</p>

        <form method="POST" action="{{ route('login') }}" class="auth-form" x-on:submit="submitting = true">
            @csrf
            <x-field :label="t('auth.emailOrPhone')" name="login">
                <x-input name="login" :value="old('login')" autocomplete="username" autocapitalize="none" spellcheck="false" required maxlength="255" :aria-invalid="$errors->has('login') ? 'true' : 'false'" />
            </x-field>
            <div>
                <label class="k-label mb-1.5 block" for="password">{{ t('auth.password') }}</label>
                <div class="auth-password">
                    <x-input id="password" type="password" x-bind:type="showPassword ? 'text' : 'password'" name="password" autocomplete="current-password" required maxlength="1024" :aria-invalid="$errors->has('password') ? 'true' : 'false'" />
                    <button type="button" class="auth-password-toggle" x-on:click="showPassword = !showPassword" x-bind:aria-pressed="showPassword" aria-controls="password" aria-label="{{ t('auth.togglePassword') }}">
                        <x-heroicon-o-eye x-show="!showPassword" aria-hidden="true" />
                        <x-heroicon-o-eye-slash x-show="showPassword" x-cloak aria-hidden="true" />
                    </button>
                </div>
                @error('password')<p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
            </div>

            <label class="auth-remember">
                <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                <span>{{ t('auth.rememberMe') }}</span>
            </label>

            <button type="submit" class="auth-submit" x-bind:disabled="submitting" x-bind:aria-busy="submitting">
                <span x-text="submitting ? @js(t('auth.signingIn')) : @js(t('auth.signIn'))">{{ t('auth.signIn') }}</span>
            </button>
        </form>

        <p class="auth-help">{{ t('auth.needHelp') }} <a href="{{ route('forgot') }}">{{ t('auth.forgotTitle') }}</a></p>
    </div>
@endsection

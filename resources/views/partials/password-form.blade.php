<form method="POST" action="{{ route('account.password') }}" class="grid gap-4" x-data="{ busy: false }" x-on:submit="busy = true">
    @csrf @method('PUT')
    <x-field :label="t('account.currentPassword')" name="current_password">
        <x-input type="password" name="current_password" autocomplete="current-password" required />
    </x-field>
    <div class="grid gap-4 sm:grid-cols-2">
        <x-field :label="t('account.newPassword')" :hint="t('account.minChars')" name="password">
            <x-input type="password" name="password" autocomplete="new-password" minlength="8" required />
        </x-field>
        <x-field :label="t('account.confirmPassword')" name="password_confirmation">
            <x-input type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required />
        </x-field>
    </div>
    <div><button class="k-btn k-btn-primary" x-bind:disabled="busy">{{ t('account.changePassword') }}</button></div>
</form>

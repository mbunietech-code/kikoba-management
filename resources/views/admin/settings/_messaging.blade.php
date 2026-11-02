@php $canManage = auth()->user()->can('settings.manage'); @endphp
<div class="grid max-w-3xl gap-6">
    <p class="text-[13px] text-neutral-600">{{ t('messaging.intro') }}</p>

    <form method="POST" action="{{ route('admin.settings.messaging') }}" class="grid gap-6">
        @csrf @method('PUT')

        <section class="rounded-md border border-neutral-200">
            <div class="flex items-center justify-between gap-3 border-b border-neutral-200 px-4 py-3">
                <div>
                    <p class="text-[14px] font-semibold text-neutral-900">{{ t('messaging.smsTitle') }}</p>
                    <p class="text-[12px] text-neutral-500">{{ t('messaging.smsHint') }}</p>
                </div>
                <x-badge :tone="$smsReady ? 'success' : 'neutral'" dot>{{ $smsReady ? t('messaging.ready') : t('messaging.notReady') }}</x-badge>
            </div>
            <div class="grid gap-4 p-4 sm:grid-cols-2">
                @if ($smsReady)
                    <div class="flex items-center justify-between gap-3 rounded-md bg-neutral-50 px-3 py-2.5 sm:col-span-2">
                        <span class="flex items-center gap-2 text-[13px] text-neutral-600"><x-heroicon-o-chat-bubble-left-right class="h-4 w-4 text-neutral-400" aria-hidden="true" /> {{ t('messaging.smsBalance') }}</span>
                        @if ($smsBalance !== null)
                            <span class="font-display text-lg font-semibold {{ $smsBalance < 50 ? 'text-red-600' : 'text-neutral-900' }}">{{ number_format($smsBalance) }} <span class="text-[12px] font-normal text-neutral-500">SMS</span></span>
                        @else
                            <span class="text-[12.5px] text-red-600">{{ t('messaging.balanceUnavailable') }}</span>
                        @endif
                    </div>
                @endif
                <label class="flex items-center gap-2 text-[13.5px] sm:col-span-2">
                    <input type="hidden" name="sms_enabled" value="0">
                    <input type="checkbox" name="sms_enabled" value="1" @checked(($messaging['sms_enabled'] ?? '0') === '1') class="h-4 w-4 accent-[var(--color-primary-800)]">
                    {{ t('messaging.sendSms') }}
                </label>
                <x-field :label="t('messaging.beemApiKey')" :hint="$messaging['beem_api_key_set'] ? t('messaging.savedKeepBlank') : null" name="beem_api_key">
                    <x-input type="password" name="beem_api_key" autocomplete="off" :placeholder="$messaging['beem_api_key_set'] ? '••••••••' : ''" />
                </x-field>
                <x-field :label="t('messaging.beemSecretKey')" :hint="$messaging['beem_secret_key_set'] ? t('messaging.savedKeepBlank') : null" name="beem_secret_key">
                    <x-input type="password" name="beem_secret_key" autocomplete="off" :placeholder="$messaging['beem_secret_key_set'] ? '••••••••' : ''" />
                </x-field>
                <x-field :label="t('messaging.senderId')" :hint="t('messaging.senderIdHint')" name="beem_sender_id">
                    <x-input name="beem_sender_id" maxlength="11" :value="$messaging['beem_sender_id'] ?? 'INFO'" />
                </x-field>
            </div>
        </section>

        <section class="rounded-md border border-neutral-200">
            <div class="flex items-center justify-between gap-3 border-b border-neutral-200 px-4 py-3">
                <div>
                    <p class="text-[14px] font-semibold text-neutral-900">{{ t('messaging.mailTitle') }}</p>
                    <p class="text-[12px] text-neutral-500">{{ t('messaging.mailHint') }}</p>
                </div>
                <x-badge :tone="$mailReady ? 'success' : 'neutral'" dot>{{ $mailReady ? t('messaging.ready') : t('messaging.notReady') }}</x-badge>
            </div>
            <div class="grid gap-4 p-4 sm:grid-cols-2">
                <label class="flex items-center gap-2 text-[13.5px] sm:col-span-2">
                    <input type="hidden" name="mail_enabled" value="0">
                    <input type="checkbox" name="mail_enabled" value="1" @checked(($messaging['mail_enabled'] ?? '0') === '1') class="h-4 w-4 accent-[var(--color-primary-800)]">
                    {{ t('messaging.sendMail') }}
                </label>
                <x-field :label="t('messaging.smtpHost')" name="mail_host"><x-input name="mail_host" :value="$messaging['mail_host']" /></x-field>
                <div class="grid grid-cols-2 gap-4">
                    <x-field :label="t('messaging.port')" name="mail_port"><x-input type="number" name="mail_port" :value="$messaging['mail_port']" /></x-field>
                    <x-field :label="t('messaging.encryption')" name="mail_encryption">
                        <x-select name="mail_encryption">@foreach (['ssl' => 'SSL (465)', 'tls' => 'TLS (587)'] as $v => $l)<option value="{{ $v }}" @selected($messaging['mail_encryption'] === $v)>{{ $l }}</option>@endforeach</x-select>
                    </x-field>
                </div>
                <x-field :label="t('messaging.mailUsername')" :hint="t('messaging.mailUsernameHint')" name="mail_username"><x-input name="mail_username" autocomplete="off" :value="$messaging['mail_username'] ?? ''" /></x-field>
                <x-field :label="t('messaging.mailPassword')" :hint="$messaging['mail_password_set'] ? t('messaging.savedKeepBlank') : null" name="mail_password">
                    <x-input type="password" name="mail_password" autocomplete="new-password" :placeholder="$messaging['mail_password_set'] ? '••••••••' : ''" />
                </x-field>
                <x-field :label="t('messaging.fromAddress')" name="mail_from_address"><x-input type="email" name="mail_from_address" :value="$messaging['mail_from_address'] ?? ''" /></x-field>
                <x-field :label="t('messaging.fromName')" name="mail_from_name"><x-input name="mail_from_name" :value="$messaging['mail_from_name'] ?? $org->name" /></x-field>
            </div>
        </section>

        @if ($canManage)<div><button class="k-btn k-btn-primary">{{ t('common.saveChanges') }}</button></div>@endif
    </form>

    @if ($canManage)
        <section class="grid gap-4 rounded-md border border-neutral-200 p-4 sm:grid-cols-2">
            <p class="text-[14px] font-semibold text-neutral-900 sm:col-span-2">{{ t('messaging.testTitle') }}</p>
            <form method="POST" action="{{ route('admin.settings.messaging.testSms') }}" class="flex items-end gap-2">@csrf
                <x-field :label="t('messaging.testPhone')" class="flex-1"><x-input name="phone" required :value="auth()->user()->phone" /></x-field>
                <button class="k-btn k-btn-outlined" @disabled(! $smsReady)>{{ t('messaging.sendTestSms') }}</button>
            </form>
            <form method="POST" action="{{ route('admin.settings.messaging.testEmail') }}" class="flex items-end gap-2">@csrf
                <x-field :label="t('messaging.testEmail')" class="flex-1"><x-input type="email" name="email" required :value="auth()->user()->email" /></x-field>
                <button class="k-btn k-btn-outlined" @disabled(! $mailReady)>{{ t('messaging.sendTestEmail') }}</button>
            </form>
        </section>
    @endif
</div>

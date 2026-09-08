@php $title = t('settings.title'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('settings.title')" :subtitle="t('settings.subtitle')" />
    <x-card flush>
        <x-tabs :current="$tab" :items="collect(['organization','modules','financial','loans','insurance','notifications'])->map(fn ($k) => ['key' => $k, 'label' => t('settings.tabs.'.$k)])->all()" />
        <div class="p-5">
            @if ($tab === 'modules')
                <form method="POST" action="{{ route('admin.settings.modules') }}" class="max-w-xl">
                    @csrf @method('PUT')
                    <p class="mb-4 text-[13px] text-neutral-500">{{ t('settings.modulesHint') }}</p>
                    <div class="divide-y divide-neutral-100 rounded-xl border border-neutral-200">
                        @foreach (['opening_shares','shares','community','insurance'] as $m)
                            <label class="flex cursor-pointer items-center justify-between gap-4 px-4 py-3">
                                <span>
                                    <span class="block text-[13.5px] font-medium text-neutral-800">{{ t('settings.modules.'.$m) }}</span>
                                    <span class="block text-[11.5px] text-neutral-400">{{ t('settings.modules.'.$m.'Desc') }}</span>
                                </span>
                                <input type="checkbox" name="module_{{ $m }}" value="1" @checked($features[$m] ?? true)
                                       class="h-5 w-5 rounded border-neutral-300 text-primary-700 focus:ring-primary-600">
                            </label>
                        @endforeach
                    </div>
                    <div class="mt-4"><button class="k-btn k-btn-primary">{{ t('common.saveChanges') }}</button></div>
                </form>
            @elseif ($tab === 'organization')
                <form method="POST" action="{{ route('admin.settings.organization') }}" class="grid max-w-2xl gap-4 sm:grid-cols-2">
                    @csrf @method('PUT')
                    <x-field :label="t('settings.organizationName')" name="name" class="sm:col-span-2"><x-input name="name" :value="old('name', $org->name)" /></x-field>
                    <x-field :label="t('settings.registrationNumber')" name="registration_number"><x-input name="registration_number" :value="old('registration_number', $org->registration_number)" /></x-field>
                    <x-field :label="t('settings.currency')" name="currency">
                        <x-select name="currency">
                            @foreach (['TZS' => 'TZS — Tanzanian Shilling', 'KES' => 'KES — Kenyan Shilling', 'UGX' => 'UGX — Ugandan Shilling', 'USD' => 'USD — US Dollar'] as $c => $lbl)
                                <option value="{{ $c }}" @selected($org->currency === $c)>{{ $lbl }}</option>
                            @endforeach
                        </x-select>
                    </x-field>
                    <x-field :label="t('common.phone')" name="phone"><x-input name="phone" :value="old('phone', $org->phone)" /></x-field>
                    <x-field :label="t('common.email')" name="email"><x-input type="email" name="email" :value="old('email', $org->email)" /></x-field>
                    <x-field :label="t('members.address')" name="address" class="sm:col-span-2"><x-input name="address" :value="old('address', $org->address)" /></x-field>
                    <div class="sm:col-span-2"><button class="k-btn k-btn-primary">{{ t('common.saveChanges') }}</button></div>
                </form>
            @else
                <form method="POST" action="{{ route('admin.settings.config') }}" class="grid max-w-2xl gap-4 sm:grid-cols-2">
                    @csrf @method('PUT')
                    @php
                        $fields = [
                            'financial' => [['share_price', t('settings.sharePrice')], ['minimum_shares', t('settings.minShares')], ['minimum_savings', t('settings.minSavings')], ['reserve_rate', t('settings.reserveRate')]],
                            'loans' => [['loan_interest_rate', t('settings.loanInterestRate')], ['penalty_rate', t('settings.penaltyRate')]],
                            'insurance' => [['insurance_contribution', t('settings.insuranceContribution')]],
                            'notifications' => [['sms_gateway', t('settings.smsGateway')], ['email_provider', t('settings.emailProvider')]],
                        ][$tab] ?? [];
                    @endphp
                    @foreach ($fields as $f)
                        <x-field :label="$f[1]"><x-input name="{{ $f[0] }}" :value="$config[$f[0]] ?? ''" /></x-field>
                    @endforeach
                    <div class="sm:col-span-2"><button class="k-btn k-btn-primary">{{ t('common.saveChanges') }}</button></div>
                </form>
            @endif
        </div>
    </x-card>
</x-layouts.app>

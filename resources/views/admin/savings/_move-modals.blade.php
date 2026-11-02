{{-- Deposit / withdrawal dialogs. $accountOptions: accounts to pick from; $fixedAccount: preselect and lock one. --}}
@php $fixedAccount ??= null; @endphp
@foreach (['dep' => ['savings.recordDeposit', 'deposit', 'savings.deposit'], 'wd' => ['savings.recordWithdrawal', 'withdraw', 'savings.withdraw']] as $key => [$titleKey, $route, $buttonKey])
    <x-modal :name="$key" :title="t($titleKey)">
        <form method="POST" action="{{ route('admin.savings.'.$route) }}" class="grid gap-4" x-data="{ busy: false }" x-on:submit="busy = true">
            @csrf
            @if ($fixedAccount)
                <input type="hidden" name="account_id" value="{{ $fixedAccount->id }}">
                <p class="rounded-md bg-neutral-50 px-3 py-2 text-[13px] text-neutral-600">
                    {{ $fixedAccount->member->full_name }} · {{ $fixedAccount->account_number }} ·
                    {{ t('common.balance') }} <b class="text-neutral-900">{{ money($fixedAccount->balance) }}</b>
                </p>
            @else
                <x-field :label="t('common.member')" name="account_id">
                    <x-select name="account_id" required>
                        <option value="">{{ t('savings.chooseMember') }}</option>
                        @foreach ($accountOptions as $option)
                            <option value="{{ $option->id }}" @selected(old('account_id') === $option->id)>{{ $option->member?->full_name }} · {{ $option->member?->member_number }} · {{ money($option->balance) }}</option>
                        @endforeach
                    </x-select>
                </x-field>
            @endif
            <x-field :label="t('common.amount').' ('.app('kikoba.currency').')'" name="amount">
                <x-input type="number" name="amount" min="1" step="1" inputmode="numeric" required value="{{ old('amount') }}" />
            </x-field>
            <x-field :label="t('common.reference')" :hint="t('savings.referenceHint')" name="reference">
                <x-input name="reference" maxlength="100" value="{{ old('reference') }}" />
            </x-field>
            <div class="flex justify-end gap-2 pt-1">
                <button type="button" class="k-btn k-btn-outlined" x-on:click="$dispatch('close-modal','{{ $key }}')">{{ t('common.cancel') }}</button>
                <button class="k-btn k-btn-primary" x-bind:disabled="busy">{{ t($buttonKey) }}</button>
            </div>
        </form>
    </x-modal>
@endforeach

@php $title = $account->member->full_name; @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="$account->member->full_name" :subtitle="$account->account_number.' · '.$account->member->member_number" :back="route('admin.savings.index')" :backLabel="t('savings.title')">
        <x-slot:actions>
            <x-btn variant="outlined" :href="route('admin.members.show', $account->member)">{{ t('savings.memberProfile') }}</x-btn>
            @can('savings.withdraw')<x-btn variant="outlined" x-data x-on:click="$dispatch('open-modal','wd')">{{ t('savings.withdraw') }}</x-btn>@endcan
            @can('savings.deposit')<x-btn icon="plus" x-data x-on:click="$dispatch('open-modal','dep')">{{ t('savings.deposit') }}</x-btn>@endcan
        </x-slot:actions>
    </x-page-header>
    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat :label="t('common.balance')" :value="money($account->balance)" />
        <x-stat :label="t('savings.totalDeposits')" :value="money($deposits)" />
        <x-stat :label="t('savings.totalWithdrawals')" :value="money($withdrawals)" />
    </div>
    <x-card class="mt-4" flush>
        <table class="w-full text-sm">
            <thead><tr class="border-b border-neutral-200 text-left text-[11px] uppercase tracking-wide text-neutral-500">
                <th class="px-4 py-3">{{ t('common.date') }}</th><th class="px-4 py-3">{{ t('common.type') }}</th>
                <th class="px-4 py-3">{{ t('common.reference') }}</th><th class="px-4 py-3 text-right">{{ t('common.amount') }}</th>
                <th class="px-4 py-3 text-right">{{ t('savings.balanceAfter') }}</th><th class="px-4 py-3"></th></tr></thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse ($txns as $s)
                    @php
                        $change = $s->balance_after - $s->balance_before;
                        $isReversed = in_array($s->id, $reversedIds, true);
                    @endphp
                    <tr class="{{ $isReversed ? 'text-neutral-400' : '' }}">
                        <td class="whitespace-nowrap px-4 py-3 text-[13px]">{{ fdate($s->created_at, 'datetime') }}</td>
                        <td class="px-4 py-3">
                            <span class="text-[13px]">{{ t('savings.txnType.'.$s->type) }}</span>
                            @if ($isReversed)<x-badge tone="neutral" class="ml-1">{{ t('transactions.statuses.reversed') }}</x-badge>@endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 font-mono text-[12px] text-neutral-500">{{ $s->reference }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right font-medium tabular-nums {{ $isReversed ? 'line-through' : ($change >= 0 ? 'text-tertiary-700' : 'text-red-700') }}">{{ $change >= 0 ? '+' : '−' }}{{ money(abs($change), symbol: false) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums">{{ money($s->balance_after) }}</td>
                        <td class="px-4 py-3 text-right">
                            @if (! $isReversed && in_array($s->type, ['deposit', 'withdrawal'], true) && $s->transaction?->status === 'successful')
                                @can('savings.withdraw')
                                    <x-reverse-action :action="route('admin.savings.reverse', $s->transaction_id)" :summary="t('savings.txnType.'.$s->type).' · '.money($s->amount).' · '.fdate($s->created_at)" />
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty :title="t('savings.noMovements')" icon="banknotes" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </x-card>
    @include('admin.savings._move-modals', ['fixedAccount' => $account])
</x-layouts.app>

@php $title = $account->member->full_name; $dep = $txns->where('type','deposit')->sum('amount'); $wd = $txns->where('type','withdrawal')->sum('amount'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="$account->member->full_name" :subtitle="$account->account_number" :back="route('admin.savings.index')" :backLabel="t('savings.title')" />
    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat tone="secondary" :label="t('common.balance')" :value="money($account->balance, true)" />
        <x-stat tone="tertiary" :label="t('savings.totalDeposits')" :value="money($dep, true)" />
        <x-stat tone="neutral" :label="t('savings.totalWithdrawals')" :value="money($wd, true)" />
    </div>
    <x-card class="mt-4" flush>
        <table class="w-full text-sm">
            <thead><tr class="border-b border-neutral-200 text-left text-[11px] uppercase tracking-wide text-neutral-500">
                <th class="px-4 py-3">{{ t('common.date') }}</th><th class="px-4 py-3">{{ t('common.type') }}</th>
                <th class="px-4 py-3">{{ t('common.reference') }}</th><th class="px-4 py-3 text-right">{{ t('common.amount') }}</th>
                <th class="px-4 py-3 text-right">{{ t('savings.balanceAfter') }}</th></tr></thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($txns->sortByDesc('created_at') as $s)
                    <tr>
                        <td class="px-4 py-3 text-[13px]">{{ fdate($s->created_at) }}</td>
                        <td class="px-4 py-3"><x-badge :tone="$s->type==='deposit'?'success':'warning'">{{ t('savings.txnType.'.$s->type) }}</x-badge></td>
                        <td class="px-4 py-3 font-mono text-[12px] text-neutral-500">{{ $s->reference }}</td>
                        <td class="px-4 py-3 text-right font-medium {{ $s->type==='deposit'?'text-tertiary-700':'text-red-600' }}">{{ $s->type==='deposit'?'+':'−' }}{{ money($s->amount, symbol:false) }}</td>
                        <td class="px-4 py-3 text-right font-semibold">{{ money($s->balance_after) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-card>
</x-layouts.app>

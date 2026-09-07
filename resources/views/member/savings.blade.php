@php
    $title = t('nav.mySavings');
    $dep = $txns->where('type','deposit')->sum('amount'); $wd = $txns->where('type','withdrawal')->sum('amount');
@endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('nav.mySavings')" :subtitle="$account?->account_number" />
    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat tone="secondary" :label="t('common.balance')" :value="money($account?->balance ?? 0, true)" />
        <x-stat tone="tertiary" :label="t('savings.totalDeposits')" :value="money($dep, true)" />
        <x-stat tone="neutral" :label="t('savings.totalWithdrawals')" :value="money($wd, true)" />
    </div>
    @if ($txns->count() > 1)
        <x-card class="mt-4">
            <div class="h-48">
                <canvas x-chart="{ type: 'area', labels: @js($txns->sortBy('created_at')->map(fn($s) => $s->created_at->format('d M'))->values()),
                    series: [{ label: 'b', color: '#2563eb', data: @js($txns->sortBy('created_at')->pluck('balance_after')->values()) }] }"></canvas>
            </div>
        </x-card>
    @endif
    <x-card class="mt-4" flush>
        <table class="w-full text-sm">
            <thead><tr class="border-b border-neutral-200 text-left text-[11px] uppercase tracking-wide text-neutral-500">
                <th class="px-4 py-3">{{ t('common.date') }}</th>
                <th class="px-4 py-3">{{ t('common.type') }}</th>
                <th class="px-4 py-3">{{ t('common.reference') }}</th>
                <th class="px-4 py-3 text-right">{{ t('common.amount') }}</th>
                <th class="px-4 py-3 text-right">{{ t('savings.balanceAfter') }}</th>
            </tr></thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse ($txns->sortByDesc('created_at') as $s)
                    <tr>
                        <td class="px-4 py-3 text-[13px] text-neutral-500">{{ fdate($s->created_at) }}</td>
                        <td class="px-4 py-3 text-[13px]">{{ t('savings.txnType.'.$s->type) }}</td>
                        <td class="px-4 py-3 font-mono text-[12px] text-neutral-500">{{ $s->reference }}</td>
                        <td class="px-4 py-3 text-right font-medium {{ $s->type === 'deposit' ? 'text-tertiary-700' : 'text-red-600' }}">
                            {{ $s->type === 'deposit' ? '+' : '−' }}{{ money($s->amount, symbol: false) }}
                        </td>
                        <td class="px-4 py-3 text-right">{{ money($s->balance_after) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty :title="t('common.noData')" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </x-card>
</x-layouts.app>

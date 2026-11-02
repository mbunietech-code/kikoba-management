@php
    $title = t('nav.mySavings');
    $reversedIds = $txns->pluck('reversal_of')->filter()->all();
    $dep = $txns->where('type','deposit')->whereNotIn('id', $reversedIds)->sum('amount');
    $wd = $txns->where('type','withdrawal')->whereNotIn('id', $reversedIds)->sum('amount');
@endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('nav.mySavings')" :subtitle="$account?->account_number" />
    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat :label="t('common.balance')" :value="money($account?->balance ?? 0)" />
        <x-stat :label="t('savings.totalDeposits')" :value="money($dep)" />
        <x-stat :label="t('savings.totalWithdrawals')" :value="money($wd)" />
    </div>
    @if ($txns->count() > 1)
        <x-card class="mt-4">
            <div class="h-48">
                <canvas x-chart="{ type: 'area', labels: @js($txns->sortBy('created_at')->map(fn($s) => $s->created_at->format('d M'))->values()),
                    series: [{ label: @js(t('common.balance')), color: '#115e59', data: @js($txns->sortBy('created_at')->pluck('balance_after')->values()) }] }"></canvas>
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
                        @php $change = $s->balance_after - $s->balance_before; @endphp
                        <td class="whitespace-nowrap px-4 py-3 text-right font-medium tabular-nums {{ in_array($s->id, $reversedIds, true) ? 'text-neutral-400 line-through' : ($change >= 0 ? 'text-tertiary-700' : 'text-red-700') }}">
                            {{ $change >= 0 ? '+' : '−' }}{{ money(abs($change), symbol: false) }}
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

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
        <x-mini-table :head="[t('common.date'), t('common.type'), t('common.reference'), t('common.amount'), t('savings.balanceAfter')]"
            :align="['','','','right','right']" html
            :rows="$txns->sortByDesc('created_at')->map(fn ($s) => [fdate($s->created_at), t('savings.txnType.'.$s->type), $s->reference,
                '<span class=\"'.($s->type==='deposit'?'text-tertiary-700':'text-red-600').' font-medium\">'.($s->type==='deposit'?'+':'−').money($s->amount, symbol:false).'</span>', money($s->balance_after)])" />
    </x-card>
</x-layouts.app>

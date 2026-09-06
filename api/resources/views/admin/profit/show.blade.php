@php $title = t('profit.title'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="fdate($distribution->period_start).' – '.fdate($distribution->period_end)" :subtitle="t('profit.basisShares')" :back="route('admin.profit.index')" :backLabel="t('profit.title')" />
    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat :label="t('profit.totalProfit')" :value="money($distribution->total_profit, true)" />
        <x-stat tone="neutral" :label="t('profit.reservedAmount')" :value="money($distribution->reserved_amount, true)" />
        <x-stat tone="tertiary" :label="t('profit.distributableProfit')" :value="money($distribution->distributable_profit, true)" />
    </div>
    <x-card class="mt-4" flush>
        <x-card-header :title="t('profit.allocation')" class="p-4" />
        <x-mini-table :head="[t('common.member'), '%', t('profit.allocation')]" :align="['','right','right']"
            :rows="$distribution->allocations->map(fn ($a) => [e($a->member->full_name), number_format($a->percentage, 2).'%', money($a->amount)])" />
    </x-card>
</x-layouts.app>

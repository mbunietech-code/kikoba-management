@php
    $title = t('nav.myLoans');
    $active = $loans->whereIn('status', ['active','overdue','disbursed'])->count();
    $out = $loans->sum('outstanding_balance');
@endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('nav.myLoans')" :subtitle="t('loans.subtitle')">
        <x-slot:actions><x-btn :href="route('member.loans.apply')" icon="plus">{{ t('member.applyLoan') }}</x-btn></x-slot:actions>
    </x-page-header>
    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat :label="t('nav.active')" :value="$active" />
        <x-stat tone="neutral" :label="t('member.outstanding')" :value="money($out, true)" />
        <x-stat tone="info" :label="t('common.total')" :value="$loans->count()" />
    </div>
    <x-card class="mt-4" flush>
        @forelse ($loans as $l)
            <a href="{{ route('member.loans.show', $l) }}" class="block border-b border-neutral-100 p-4 last:border-0 hover:bg-neutral-50">
                <div class="flex items-center justify-between">
                    <span class="font-medium text-primary-700">{{ $l->loan_number }}</span>
                    <x-status :status="$l->status" :label="t('loans.status.'.$l->status)" />
                </div>
                <p class="text-[12px] text-neutral-500">{{ $l->product->name }}</p>
                <div class="mt-2 flex gap-6 text-[13px]">
                    <span><span class="text-neutral-400">{{ t('loans.principal') }}</span> <b>{{ money($l->principal_amount, true) }}</b></span>
                    <span><span class="text-neutral-400">{{ t('loans.outstanding') }}</span> <b>{{ money($l->outstanding_balance, true) }}</b></span>
                </div>
                <div class="mt-2"><x-progress :value="$l->total_amount ? $l->amount_paid / $l->total_amount * 100 : 0" showLabel :tone="$l->status==='overdue'?'danger':'primary'" /></div>
            </a>
        @empty
            <x-empty :title="t('common.noData')" :hint="t('loans.subtitle')">
                <x-slot:action><x-btn :href="route('member.loans.apply')">{{ t('member.applyLoan') }}</x-btn></x-slot:action>
            </x-empty>
        @endforelse
    </x-card>
</x-layouts.app>

@php $title = t('products.title'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('products.title')" :subtitle="t('products.subtitle')">
        <x-slot:actions><x-btn :href="route('admin.products.create')" icon="plus">{{ t('products.addProduct') }}</x-btn></x-slot:actions>
    </x-page-header>
    <div class="grid gap-4 md:grid-cols-2">
        @forelse ($products as $p)
            <x-card class="k-row-anim">
                <div class="flex items-start justify-between">
                    <div><h3 class="font-display text-base font-bold text-neutral-900">{{ $p->name }}</h3><p class="mt-0.5 text-[13px] text-neutral-500">{{ $p->description }}</p></div>
                    <div class="flex items-center gap-1">
                        <x-badge :tone="$p->status==='active'?'success':'neutral'">{{ $p->status }}</x-badge>
                        <x-row-actions :edit="route('admin.products.edit',$p)" :delete="route('admin.products.destroy',$p)" />
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-[13px]">
                    @foreach ([
                        [t('products.minAmount'), money($p->minimum_amount, true)],
                        [t('products.maxAmount'), money($p->maximum_amount, true)],
                        [t('products.interestRate'), pct($p->interest_rate).' · '.t('loans.method.'.$p->interest_method)],
                        [t('loans.period'), $p->repayment_period.' '.t('loans.months')],
                        [t('products.processingFee'), pct($p->processing_fee)],
                        [t('products.minSavings'), money($p->minimum_savings, true)],
                    ] as $r)
                        <div><p class="text-[11px] uppercase tracking-wide text-neutral-400">{{ $r[0] }}</p><p class="mt-0.5 font-medium text-neutral-800">{{ $r[1] }}</p></div>
                    @endforeach
                </div>
                <p class="mt-4 border-t border-neutral-100 pt-3 text-[13px] text-neutral-500">{{ $p->required_guarantors }} {{ strtolower(t('products.requiredGuarantors')) }}</p>
            </x-card>
        @empty
            <x-card><x-empty :title="t('common.noData')" /></x-card>
        @endforelse
    </div>
</x-layouts.app>

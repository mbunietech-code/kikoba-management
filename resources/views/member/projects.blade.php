@php
    $title = t('nav.myProjects');
    $invested = $investments->sum('amount');
    $returns = $investments->sum(fn ($i) => $i->amount + $i->profit_share);
@endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('nav.myProjects')" :subtitle="t('projects.subtitle')" />
    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat :label="t('projects.myInvestment')" :value="money($invested, true)" />
        <x-stat tone="tertiary" :label="t('projects.totalReturn')" :value="money($returns, true)" />
        <x-stat tone="info" :label="t('common.total')" :value="$investments->count()" />
    </div>
    <div class="mt-4 grid gap-4 md:grid-cols-2">
        @forelse ($investments as $iv)
            @php $p = $iv->project; $pct = $p->capital_required ? $p->capital_raised / $p->capital_required * 100 : 0; @endphp
            <x-card :href="route('member.projects.show', $p)">
                <div class="flex items-start justify-between">
                    <h3 class="font-display text-base font-bold text-neutral-900">{{ $p->name }}</h3>
                    <x-badge :tone="['active'=>'success','completed'=>'info'][$p->status] ?? 'neutral'">{{ t('projects.status.'.$p->status) }}</x-badge>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-3 text-[13px]">
                    <div><p class="text-[11px] uppercase text-neutral-400">{{ t('projects.myInvestment') }}</p><p class="font-medium">{{ money($iv->amount) }}</p></div>
                    <div><p class="text-[11px] uppercase text-neutral-400">{{ t('projects.profitShare') }}</p><p class="font-medium">{{ money($iv->profit_share) }}</p></div>
                </div>
                <div class="mt-3"><x-progress :value="$pct" showLabel :tone="$pct >= 100 ? 'tertiary' : 'primary'" /></div>
            </x-card>
        @empty
            <x-card><x-empty :title="t('common.noData')" /></x-card>
        @endforelse
    </div>
</x-layouts.app>

@php $title = t('projects.title'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('projects.title')" :subtitle="t('projects.subtitle')">
        <x-slot:actions><x-btn :href="route('admin.projects.create')" icon="plus">{{ t('projects.addProject') }}</x-btn></x-slot:actions>
    </x-page-header>
    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat :label="t('dashboard.activeProjects')" :value="$projects->where('status','active')->count()" />
        <x-stat tone="secondary" :label="t('dashboard.projectCapital')" :value="money($projects->sum('capital_raised'), true)" />
        <x-stat tone="tertiary" :label="t('projects.actualProfit')" :value="money($projects->sum('actual_profit'), true)" />
    </div>
    <div class="mt-4 grid gap-4 md:grid-cols-2">
        @forelse ($projects as $p)
            @php $pct = $p->capital_required ? $p->capital_raised / $p->capital_required * 100 : 0; @endphp
            <x-card class="k-row-anim">
                <div class="flex items-start justify-between gap-3">
                    <a href="{{ route('admin.projects.show', $p) }}" class="min-w-0">
                        <h3 class="font-display text-base font-bold text-neutral-900">{{ $p->name }}</h3>
                        <p class="mt-0.5 line-clamp-2 text-[13px] text-neutral-500">{{ $p->description }}</p>
                    </a>
                    <div class="flex items-center gap-1">
                        <x-badge :tone="['active'=>'success','completed'=>'info','planned'=>'warning'][$p->status] ?? 'neutral'">{{ t('projects.status.'.$p->status) }}</x-badge>
                        <x-row-actions :edit="route('admin.projects.edit', $p)" :delete="route('admin.projects.destroy', $p)" />
                    </div>
                </div>
                <div class="mt-4">
                    <div class="mb-1 flex justify-between text-[12px] text-neutral-500"><span>{{ t('projects.fundingProgress') }}</span><span>{{ money($p->capital_raised, true) }} / {{ money($p->capital_required, true) }}</span></div>
                    <x-progress :value="$pct" :tone="$pct >= 100 ? 'tertiary' : 'primary'" showLabel />
                </div>
                <div class="mt-4 flex items-center justify-between border-t border-neutral-100 pt-3 text-[13px] text-neutral-500">
                    <span>{{ $p->participant_count }} · {{ t('projects.participants') }}</span>
                    <span>{{ t('projects.type.'.$p->type) }}</span>
                    <span>{{ fdate($p->end_date, 'short') }}</span>
                </div>
            </x-card>
        @empty
            <x-card><x-empty :title="t('common.noData')" /></x-card>
        @endforelse
    </div>
</x-layouts.app>

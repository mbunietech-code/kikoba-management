@php $title = $project->name; $pct = $project->capital_required ? $project->capital_raised / $project->capital_required * 100 : 0; @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="$project->name" :subtitle="t('projects.type.'.$project->type)" :back="route('admin.projects.index')" :backLabel="t('projects.title')">
        <x-slot:actions><x-btn variant="outlined" :href="route('admin.projects.edit', $project)" icon="pencil-square">{{ t('common.edit') }}</x-btn></x-slot:actions>
    </x-page-header>
    <div class="grid gap-4 lg:grid-cols-[320px_1fr]">
        <div class="flex flex-col gap-4">
            <x-card>
                <div class="mb-1 flex justify-between text-[12px] text-neutral-500"><span>{{ t('projects.fundingProgress') }}</span><span>{{ round($pct) }}%</span></div>
                <x-progress :value="$pct" :tone="$pct >= 100 ? 'tertiary' : 'primary'" />
                <p class="mt-2 text-[13px] text-neutral-600">{{ money($project->capital_raised) }} / {{ money($project->capital_required) }}</p>
            </x-card>
            <div class="grid grid-cols-2 gap-3">
                <x-stat tone="secondary" :label="t('projects.expectedProfit')" :value="money($project->expected_profit, true)" />
                <x-stat tone="tertiary" :label="t('projects.actualProfit')" :value="money($project->actual_profit, true)" />
            </div>
            <x-card>
                <x-card-header :title="t('common.details')" />
                <x-kv :cols="1" :items="[
                    ['label' => t('projects.manager'), 'value' => e($project->manager)],
                    ['label' => t('projects.startDate'), 'value' => fdate($project->start_date)],
                    ['label' => t('projects.endDate'), 'value' => fdate($project->end_date)],
                    ['label' => t('projects.participants'), 'value' => $project->participant_count],
                    ['label' => t('common.status'), 'value' => t('projects.status.'.$project->status)],
                ]" />
            </x-card>
        </div>
        <x-card flush class="min-w-0">
            <x-card-header :title="t('projects.participants')" class="p-4" />
            <x-mini-table :head="[t('common.member'), t('projects.myInvestment'), t('projects.profitShare'), t('projects.totalReturn'), t('common.date')]"
                :align="['','right','right','right','']"
                :rows="$project->investments->map(fn ($i) => [e($i->member->full_name), money($i->amount), money($i->profit_share), money($i->amount + $i->profit_share), fdate($i->invested_at)])" />
        </x-card>
    </div>
</x-layouts.app>

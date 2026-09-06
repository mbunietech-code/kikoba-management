@php $title = $project->name; $pct = $project->capital_required ? $project->capital_raised / $project->capital_required * 100 : 0; @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="$project->name" :subtitle="t('projects.type.'.$project->type)" :back="route('member.projects')" :backLabel="t('nav.myProjects')" />
    <div class="grid gap-4 lg:grid-cols-[1fr_340px]">
        <x-card>
            <p class="text-[14px] text-neutral-600">{{ $project->description }}</p>
            <div class="mt-4">
                <div class="mb-1 flex justify-between text-[12px] text-neutral-500"><span>{{ t('projects.fundingProgress') }}</span><span>{{ round($pct) }}%</span></div>
                <x-progress :value="$pct" :tone="$pct >= 100 ? 'tertiary' : 'primary'" />
                <p class="mt-2 text-[13px] text-neutral-600">{{ money($project->capital_raised) }} / {{ money($project->capital_required) }}</p>
            </div>
            <div class="mt-5">
                <x-kv :items="[
                    ['label' => t('projects.manager'), 'value' => e($project->manager)],
                    ['label' => t('projects.participants'), 'value' => $project->participant_count],
                    ['label' => t('projects.startDate'), 'value' => fdate($project->start_date)],
                    ['label' => t('projects.endDate'), 'value' => fdate($project->end_date)],
                    ['label' => t('projects.expectedProfit'), 'value' => money($project->expected_profit)],
                    ['label' => t('common.status'), 'value' => t('projects.status.'.$project->status)],
                ]" />
            </div>
        </x-card>
        <div class="flex flex-col gap-4">
            @if ($mine)
                <x-card>
                    <x-card-header :title="t('projects.myInvestment')" />
                    <p class="mt-1 font-display text-2xl font-bold text-neutral-900">{{ money($mine->amount) }}</p>
                    <div class="mt-3 space-y-1.5 text-[13px]">
                        <div class="flex justify-between"><span class="text-neutral-500">{{ t('projects.profitShare') }}</span><span class="font-medium">{{ money($mine->profit_share) }}</span></div>
                        <div class="flex justify-between border-t border-neutral-100 pt-1.5"><span class="font-semibold">{{ t('projects.totalReturn') }}</span><span class="font-bold">{{ money($mine->amount + $mine->profit_share) }}</span></div>
                    </div>
                </x-card>
            @endif
            <x-stat tone="tertiary" :label="t('projects.actualProfit')" :value="money($project->actual_profit, true)" />
        </div>
    </div>
</x-layouts.app>

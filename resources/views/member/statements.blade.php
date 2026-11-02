@php $title = t('nav.statements'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('nav.statements')" :subtitle="t('statements.subtitle')" />
    <div class="grid gap-4 lg:grid-cols-[1fr_340px]">
        <x-card>
            <x-card-header :title="t('statements.generate')" :subtitle="t('statements.whatIsIncluded')" />
            <form method="GET" action="{{ route('member.statements.download') }}" class="flex flex-wrap items-end gap-3">
                <x-field :label="t('statements.period')" class="min-w-[200px] flex-1">
                    <x-select name="period">
                        <option value="month">{{ t('common.thisMonth') }}</option>
                        <option value="year">{{ t('common.thisYear') }}</option>
                        <option value="all">{{ t('statements.allTime') }}</option>
                    </x-select>
                </x-field>
                <button class="k-btn k-btn-primary"><x-heroicon-o-arrow-down-tray class="h-4 w-4" /> {{ t('statements.downloadCsv') }}</button>
            </form>
        </x-card>
        <x-card>
            <x-card-header :title="t('statements.positionToday')" :subtitle="$member->full_name.' · '.$member->member_number" />
            <dl class="space-y-2 text-[13.5px]">
                @foreach ([
                    [t('member.shareValue'), $pos['shareValue']],
                    [t('member.savingsBalance'), $pos['savingsBalance']],
                    [t('member.projectInvestment'), $pos['projectInvestment']],
                    [t('member.outstanding'), -$pos['loanOutstanding']],
                ] as [$label, $value])
                    <div class="flex justify-between"><dt class="text-neutral-500">{{ $label }}</dt><dd class="font-medium tabular-nums {{ $value < 0 ? 'text-red-700' : '' }}">{{ money($value) }}</dd></div>
                @endforeach
                <div class="flex justify-between border-t border-neutral-100 pt-2"><dt class="font-semibold">{{ t('statements.netPosition') }}</dt>
                    <dd class="font-bold tabular-nums">{{ money($pos['shareValue'] + $pos['savingsBalance'] + $pos['projectInvestment'] - $pos['loanOutstanding']) }}</dd></div>
            </dl>
        </x-card>
    </div>
</x-layouts.app>

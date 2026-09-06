@php $title = t('nav.statements'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('nav.statements')" :subtitle="t('statements.subtitle')" />
    <div class="grid gap-4 lg:grid-cols-[1fr_340px]">
        <x-card>
            <x-card-header :title="t('statements.generate')" />
            <div class="mt-2 grid gap-4">
                <x-field :label="t('statements.period')">
                    <x-select><option>{{ t('common.thisMonth') }}</option><option>{{ t('common.thisYear') }}</option><option>2025</option></x-select>
                </x-field>
                <x-btn icon="arrow-down-tray" onclick="window.toast('{{ t('statements.generate') }} ✓')">{{ t('statements.generate') }}</x-btn>
                <div class="rounded-xl bg-neutral-50 p-4">
                    <p class="text-[13px] font-semibold text-neutral-700">{{ $member->full_name }} · {{ $member->member_number }}</p>
                    <div class="mt-3">
                        <x-kv :items="[
                            ['label' => t('member.shareValue'), 'value' => money($pos['shareValue'])],
                            ['label' => t('member.savingsBalance'), 'value' => money($pos['savingsBalance'])],
                            ['label' => t('member.outstanding'), 'value' => money($pos['loanOutstanding'])],
                            ['label' => t('member.projectInvestment'), 'value' => money($pos['projectInvestment'])],
                            ['label' => t('member.myProfit'), 'value' => money($pos['profit'])],
                            ['label' => t('statements.closing'), 'value' => '<b>'.money($pos['shareValue'] + $pos['savingsBalance'] + $pos['projectInvestment']).'</b>'],
                        ]" />
                    </div>
                </div>
            </div>
        </x-card>
        <x-card flush>
            <x-card-header :title="t('reports.generatedReports')" class="p-4" />
            <ul class="divide-y divide-neutral-100">
                @foreach ([['Q2 2026', '2026-06-30'], ['Q1 2026', '2026-03-31'], ['2025', '2025-12-31']] as $h)
                    <li class="flex items-center justify-between px-4 py-3">
                        <div class="flex items-center gap-3">
                            <x-heroicon-o-document-text class="h-4 w-4 text-neutral-400" />
                            <div><p class="text-[13px] font-medium">{{ t('statements.title') }} — {{ $h[0] }}</p><p class="text-[12px] text-neutral-400">{{ fdate($h[1]) }}</p></div>
                        </div>
                        <x-badge tone="neutral">PDF</x-badge>
                    </li>
                @endforeach
            </ul>
        </x-card>
    </div>
</x-layouts.app>

@php $title = t('nav.home'); $first = str($member->full_name)->before(' '); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('member.greeting', ['name' => $first])" :subtitle="t('member.myPosition')" />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat :label="t('member.shareValue')" :value="money($pos['shareValue'])" :hint="num($pos['shareQty']).' '.strtolower(t('shares.quantity'))" />
        <x-stat :label="t('member.savingsBalance')" :value="money($pos['savingsBalance'])" />
        <x-stat :label="t('member.outstanding')" :value="money($pos['loanOutstanding'])" />
        <x-stat :label="t('member.myProfit')" :value="money($pos['profit'])" :hint="t('member.profitHint')" />
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        <x-card class="lg:col-span-2">
            <x-card-header :title="t('member.savingsOverTime')" :subtitle="t('member.monthEndBalance').' · '.$savingsTrend['period']" />
            <div class="h-52">
                <canvas x-chart="{ type: 'bar', labels: @js($savingsTrend['labels']),
                    series: [{ label: @js(t('member.savingsBalance')), color: '#115e59', data: @js($savingsTrend['balances']) }] }"></canvas>
            </div>
        </x-card>
        <div class="flex flex-col gap-4">
            @if ($nextRepay)
                <x-card>
                    <p class="text-[13px] font-medium text-neutral-500">{{ t('member.nextRepayment') }}</p>
                    <p class="mt-1 font-display text-xl font-bold {{ $nextRepay->status === 'overdue' ? 'text-red-700' : 'text-neutral-900' }}">{{ money($nextRepay->total_due - $nextRepay->amount_paid) }}</p>
                    <div class="mt-1 flex items-center gap-2">
                        <span class="text-[13px] text-neutral-500">{{ fdate($nextRepay->due_date) }}</span>
                        @if ($nextRepay->status === 'overdue')<x-badge tone="danger" dot>{{ t('loans.scheduleStatus.overdue') }}</x-badge>@endif
                    </div>
                    <p class="mt-2 text-[12.5px] text-neutral-500">{{ t('member.payToTreasurer') }}</p>
                    <x-btn variant="outlined" :href="route('member.loans.show', $pos['activeLoan'])" size="sm" class="mt-3 w-full">{{ t('member.viewLoan') }}</x-btn>
                </x-card>
            @endif
            <x-card>
                <p class="text-[13px] font-medium text-neutral-500">{{ t('member.insuranceStatus') }}</p>
                <div class="mt-1.5">
                    @if ($pos['insurance'])<x-status :status="$pos['insurance']->status" :label="t('insurance.status.'.$pos['insurance']->status)" />@else<x-badge tone="neutral">—</x-badge>@endif
                </div>
                @if ($pos['insurance'])<p class="mt-2 text-[13px] text-neutral-500">{{ t('insurance.coverageAmount') }}: {{ money($pos['insurance']->coverage_amount) }}</p>@endif
            </x-card>
        </div>
    </div>

    <x-card class="mt-4">
        <x-card-header :title="t('member.quickActions')" />
        <div class="mt-2 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([[t('member.applyLoan'), route('member.loans.apply')], [t('member.seeSavings'), route('member.savings')], [t('member.seeRepayments'), route('member.repayments')], [t('member.downloadStatement'), route('member.statements')]] as [$label, $href])
                <a href="{{ $href }}" class="flex items-center justify-between gap-3 rounded-md border border-neutral-200 px-4 py-3 text-[13.5px] font-medium text-neutral-700 hover:border-primary-300 hover:bg-primary-50/40">
                    {{ $label }} <x-heroicon-o-chevron-right class="h-4 w-4 text-neutral-400" />
                </a>
            @endforeach
        </div>
    </x-card>

    <x-card class="mt-4" flush>
        <div class="flex items-center justify-between px-4 pt-4">
            <p class="text-[15px] font-bold text-neutral-900">{{ t('common.recent') }} — {{ t('nav.transactions') }}</p>
            <a href="{{ route('member.transactions') }}" class="text-[13px] font-medium text-primary-700 hover:underline">{{ t('common.viewAll') }}</a>
        </div>
        <ul class="mt-2 divide-y divide-neutral-100">
            @foreach ($txns as $tx)
                <li class="flex items-center justify-between px-4 py-3">
                    <div><p class="text-[13.5px] font-medium text-neutral-800">{{ t('transactions.types.'.strtolower($tx->type)) }}</p><p class="text-[12px] text-neutral-400">{{ $tx->transaction_reference }}</p></div>
                    <span class="text-[13.5px] font-semibold text-neutral-800">{{ money($tx->amount) }}</span>
                </li>
            @endforeach
        </ul>
    </x-card>
</x-layouts.app>

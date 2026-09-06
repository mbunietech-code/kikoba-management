@php $title = t('nav.home'); $first = str($member->full_name)->before(' '); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('member.greeting', ['name' => $first])" :subtitle="t('member.myPosition')" />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat :label="t('member.shareValue')" :value="money($pos['shareValue'], true)" :hint="$pos['shareQty'].' '.strtolower(t('shares.quantity'))" />
        <x-stat tone="secondary" :label="t('member.savingsBalance')" :value="money($pos['savingsBalance'], true)" />
        <x-stat tone="neutral" :label="t('member.outstanding')" :value="money($pos['loanOutstanding'], true)" />
        <x-stat tone="tertiary" :label="t('member.myProfit')" :value="money($pos['profit'], true)" />
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        <x-card class="lg:col-span-2">
            <x-card-header :title="t('member.savingsBalance')" subtitle="Apr – Sep 2026" />
            <div class="h-52">
                <canvas x-chart="{ type: 'area', labels: ['Apr','May','Jun','Jul','Aug','Sep'],
                    series: [{ label: 's', color: '#2563eb', data: @js(collect(range(0,5))->map(fn($i) => (int) round($pos['savingsBalance'] * (0.6 + $i * 0.08)))) }] }"></canvas>
            </div>
        </x-card>
        <div class="flex flex-col gap-4">
            @if ($nextRepay)
                <x-card>
                    <p class="text-[13px] font-medium text-neutral-500">{{ t('member.nextRepayment') }}</p>
                    <p class="mt-1 font-display text-xl font-bold text-neutral-900">{{ money($nextRepay->total_due) }}</p>
                    <div class="mt-1 flex items-center gap-2">
                        <span class="text-[13px] text-neutral-500">{{ fdate($nextRepay->due_date) }}</span>
                        @if ($nextRepay->status === 'overdue')<x-badge tone="danger" dot>{{ t('loans.scheduleStatus.overdue') }}</x-badge>@endif
                    </div>
                    <x-btn :href="route('member.loans.show', $pos['activeLoan'])" size="sm" class="mt-3 w-full">{{ t('loans.recordRepayment') }}</x-btn>
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
            @foreach ([[t('member.applyLoan'), 'credit-card', route('member.loans.apply')], [t('member.makeDeposit'), 'banknotes', route('member.savings')], [t('member.buyShares'), 'plus-circle', route('member.shares')], [t('member.downloadStatement'), 'arrow-down-tray', route('member.statements')]] as $a)
                <a href="{{ $a[2] }}" class="flex items-center gap-3 rounded-xl border border-neutral-200 p-4 hover:border-primary-300 hover:bg-primary-50/50">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-50 text-primary-700"><x-dynamic-component :component="'heroicon-o-'.$a[1]" class="h-5 w-5" /></span>
                    <span class="text-[13.5px] font-medium text-neutral-700">{{ $a[0] }}</span>
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

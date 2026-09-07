@php $title = t('nav.dashboard'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('dashboard.welcome', ['name' => str(auth()->user()->name)->before(' ')])" :subtitle="t('dashboard.overview')">
        <x-slot:actions>
            <x-btn variant="outlined" :href="route('admin.reports.index')">{{ t('nav.reports') }}</x-btn>
            <x-btn :href="route('admin.members.create')" icon="user-plus">{{ t('members.addMember') }}</x-btn>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat :i="0" :label="t('dashboard.totalMembers')" :value="$summary['totalMembers']" icon="users" :delta="'+'.$summary['newMembers']" :hint="strtolower(t('dashboard.newMembers'))" />
        <x-stat :i="1" tone="secondary" :label="t('dashboard.totalSavings')" :value="money($summary['totalSavings'], true)" icon="banknotes" />
        <x-stat :i="2" tone="tertiary" :label="t('dashboard.totalShares')" :value="money($summary['totalShares'], true)" icon="chart-pie" />
        <x-stat :i="3" tone="neutral" :label="t('dashboard.outstandingLoans')" :value="money($summary['outstanding'], true)" icon="credit-card" :hint="t('dashboard.portfolioAtRisk').' '.pct($summary['par']*100)" />
        <x-stat :i="4" :label="t('dashboard.totalLoans')" :value="money($summary['disbursed'], true)" icon="arrow-up-tray" />
        <x-stat :i="5" tone="tertiary" :label="t('dashboard.totalRepayments')" :value="money($summary['repayments'], true)" icon="arrow-trending-up" />
        <x-stat :i="6" tone="secondary" :label="t('dashboard.totalProfit')" :value="money($summary['netProfit'], true)" icon="arrow-trending-up" />
        <x-stat :i="7" tone="neutral" :label="t('dashboard.projectCapital')" :value="money($summary['projectCapital'], true)" icon="briefcase" :hint="$summary['activeProjects'].' '.strtolower(t('dashboard.activeProjects'))" />
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        <x-card class="lg:col-span-2">
            <x-card-header :title="t('dashboard.cashFlow')" subtitle="Mar – Sep 2026" />
            <div class="h-56">
                <canvas x-chart="{ type: 'area', labels: @js($cashFlow['labels']), series: [
                    { label: 'in', data: @js($cashFlow['inflow']), color: '#115e59' },
                    { label: 'out', data: @js($cashFlow['outflow']), color: '#dc2626' }
                ] }"></canvas>
            </div>
        </x-card>
        <x-card>
            <x-card-header :title="t('dashboard.loanStatus')" />
            <div class="h-56">
                <canvas x-chart="{ type: 'doughnut',
                    labels: @js($loanStatus->keys()->map(fn($k) => t('loans.status.'.$k))->values()),
                    data: @js($loanStatus->values()) }"></canvas>
            </div>
        </x-card>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <x-card flush>
            <div class="flex items-center justify-between px-4 pt-4">
                <p class="text-[15px] font-bold text-neutral-900">{{ t('dashboard.pendingApprovals') }}</p>
                <a href="{{ route('admin.loans.index') }}" class="text-[13px] font-medium text-primary-700 hover:underline">{{ t('common.viewAll') }}</a>
            </div>
            <ul class="mt-2 divide-y divide-neutral-100">
                @forelse ($pendingApprovals as $l)
                    <li>
                        <a href="{{ route('admin.loans.show', $l) }}" class="flex items-center gap-3 px-4 py-3 hover:bg-neutral-50">
                            <x-avatar :name="$l->member?->full_name ?? '—'" :color="$l->member?->avatar_color" size="sm" />
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[13.5px] font-medium text-neutral-800">{{ $l->member?->full_name ?? t('common.noData') }}</span>
                                <span class="block text-[11px] text-neutral-400">{{ $l->product?->name }}</span>
                            </span>
                            <span class="text-[13px] font-semibold">{{ money($l->principal_amount, true) }}</span>
                            <x-status :status="$l->status" :label="t('loans.status.'.$l->status)" />
                        </a>
                    </li>
                @empty
                    <li class="px-4 py-6 text-sm text-neutral-400">{{ t('common.noData') }}</li>
                @endforelse
            </ul>
        </x-card>

        <x-card flush>
            <p class="px-4 pt-4 text-[15px] font-bold text-neutral-900">{{ t('dashboard.upcomingRepayments') }}</p>
            <ul class="mt-2 divide-y divide-neutral-100">
                @forelse ($upcoming as $r)
                    <li>
                        <a href="{{ route('admin.loans.show', $r->loan) }}" class="flex items-center gap-3 px-4 py-3 hover:bg-neutral-50">
                            <x-avatar :name="$r->loan?->member?->full_name ?? '—'" :color="$r->loan?->member?->avatar_color" size="sm" />
                            <span class="min-w-0 flex-1 truncate text-[13.5px] font-medium text-neutral-800">{{ $r->loan?->member?->full_name ?? t('common.noData') }}</span>
                            <span class="text-right">
                                <span class="block text-[13px] font-semibold">{{ money($r->total_due, true) }}</span>
                                <span class="block text-[11px] text-neutral-400">{{ fdate($r->due_date) }}</span>
                            </span>
                            <x-badge :tone="$r->status === 'overdue' ? 'danger' : 'warning'" dot>{{ t('loans.scheduleStatus.'.$r->status) }}</x-badge>
                        </a>
                    </li>
                @empty
                    <li class="px-4 py-6 text-sm text-neutral-400">{{ t('common.noData') }}</li>
                @endforelse
            </ul>
        </x-card>
    </div>

    <x-card class="mt-4" flush>
        <div class="flex items-center justify-between px-4 pt-4">
            <p class="text-[15px] font-bold text-neutral-900">{{ t('dashboard.recentTransactions') }}</p>
            <a href="{{ route('admin.transactions.index') }}" class="text-[13px] font-medium text-primary-700 hover:underline">{{ t('common.viewAll') }}</a>
        </div>
        <ul class="mt-2 divide-y divide-neutral-100">
            @foreach ($recent as $tx)
                <li class="flex items-center gap-3 px-4 py-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-neutral-100 text-[11px] font-semibold text-neutral-500">{{ initials($tx->member?->full_name) }}</span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-[13px] font-medium text-neutral-800">{{ t('transactions.types.'.strtolower($tx->type)) }}</span>
                        <span class="block text-[11px] text-neutral-400">{{ $tx->transaction_reference }}</span>
                    </span>
                    <span class="text-[13px] font-semibold">{{ money($tx->amount) }}</span>
                    <x-status :status="$tx->status" />
                </li>
            @endforeach
        </ul>
    </x-card>
</x-layouts.app>

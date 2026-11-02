@php
    $title = $loan->loan_number;
    $workflow = ['submitted','under_review','approved','disbursed','active','completed'];
    $stepStatus = in_array($loan->status, ['overdue', 'defaulted'], true) ? 'active' : $loan->status;
    $step = array_search($stepStatus, $workflow, true);
    $canDecide = in_array($loan->status, ['submitted','under_review']);
    $isRunning = in_array($loan->status, ['active','overdue','disbursed','defaulted'], true);
    $nextDue = $loan->schedule->first(fn ($r) => $r->status !== 'paid');
    $nextDueAmount = $nextDue ? max(0, $nextDue->total_due - $nextDue->amount_paid) : 0;
    $overdueRows = $loan->schedule->where('status', 'overdue');
    $overdueAmount = $overdueRows->sum(fn ($r) => $r->total_due - $r->amount_paid);
@endphp
<x-layouts.app :title="$title">
    <x-page-header :title="$loan->loan_number" :subtitle="$loan->product->name.' · '.$loan->member->full_name" :back="route('admin.loans.index')" :backLabel="t('loans.title')">
        <x-slot:actions>
            @if ($canDecide)
                @can('loans.reject')<x-btn variant="outlined" x-data x-on:click="$dispatch('open-modal','reject')">{{ t('common.reject') }}</x-btn>@endcan
                @can('loans.approve')<x-btn x-data x-on:click="$dispatch('open-modal','approve')">{{ t('common.approve') }}</x-btn>@endcan
            @elseif ($loan->status === 'approved')
                @can('loans.reject')<x-btn variant="outlined" x-data x-on:click="$dispatch('open-modal','reject')">{{ t('common.reject') }}</x-btn>@endcan
                @can('loans.disburse')<x-btn icon="banknotes" x-data x-on:click="$dispatch('open-modal','disburse')">{{ t('loans.disburse') }}</x-btn>@endcan
            @elseif ($isRunning)
                @can('loans.repay')<x-btn icon="plus" x-data x-on:click="$dispatch('open-modal','repay')">{{ t('loans.recordRepayment') }}</x-btn>@endcan
            @endif
        </x-slot:actions>
    </x-page-header>

    @if (in_array($loan->status, ['rejected', 'cancelled'], true))
        <div class="mb-4 rounded-md border border-neutral-200 bg-neutral-50 px-4 py-3 text-[13.5px] text-neutral-700">
            {{ t('loans.closedBanner', ['status' => strtolower(t('loans.status.'.$loan->status))]) }}
        </div>
    @elseif ($overdueRows->isNotEmpty())
        <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-[13.5px] text-red-800">
            {{ t('loans.overdueBanner', ['count' => $overdueRows->count(), 'amount' => money($overdueAmount), 'date' => fdate($overdueRows->first()->due_date)]) }}
        </div>
    @endif

    <x-card class="mb-4">
        <div class="flex flex-wrap items-center gap-y-3">
            @foreach ($workflow as $i => $s)
                @php $done = $step !== false && $i <= $step; @endphp
                <div class="flex items-center">
                    <div class="flex flex-col items-center gap-1.5">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full border text-[12px] font-semibold {{ $done ? 'border-primary-700 bg-primary-700 text-white' : 'border-neutral-300 bg-white text-neutral-400' }}">
                            {{ $done ? '✓' : $i + 1 }}
                        </span>
                        <span class="text-[11px] font-medium {{ $done ? 'text-neutral-700' : 'text-neutral-400' }}">{{ t('loans.status.'.$s) }}</span>
                    </div>
                    @if ($i < count($workflow) - 1)<span class="mx-2 h-0.5 w-8 sm:w-12 {{ ($step !== false && $i < $step) ? 'bg-primary-500' : 'bg-neutral-200' }}"></span>@endif
                </div>
            @endforeach
        </div>
    </x-card>

    <div class="grid gap-4 lg:grid-cols-[320px_1fr]">
        <div class="flex flex-col gap-4">
            <x-card>
                <p class="text-[12.5px] font-medium text-neutral-500">{{ t('loans.outstanding') }}</p>
                <p class="mt-1 font-display text-[22px] font-bold text-neutral-900">{{ money($loan->outstanding_balance) }}</p>
                <div class="mt-3"><x-progress :value="$loan->total_amount ? $loan->amount_paid / $loan->total_amount * 100 : 0" showLabel :tone="$loan->status==='overdue'?'danger':'tertiary'" /></div>
                <p class="mt-1.5 text-[12px] text-neutral-500">{{ t('loans.paidOfTotal', ['paid' => money($loan->amount_paid), 'total' => money($loan->total_amount)]) }}</p>
                @if ($isRunning && $nextDue)
                    <div class="mt-4 border-t border-neutral-100 pt-3">
                        <p class="text-[12.5px] font-medium text-neutral-500">{{ t('loans.nextPayment') }}</p>
                        <p class="mt-0.5 text-[15px] font-semibold {{ $nextDue->status === 'overdue' ? 'text-red-700' : 'text-neutral-900' }}">{{ money($nextDueAmount) }}</p>
                        <p class="text-[12px] text-neutral-500">{{ t('loans.dueOn', ['date' => fdate($nextDue->due_date)]) }}</p>
                    </div>
                @endif
            </x-card>
            <x-card>
                <x-card-header :title="t('common.summary')" />
                <x-kv :cols="1" :items="[
                    ['label' => t('loans.principal'), 'value' => money($loan->principal_amount)],
                    ['label' => t('loans.interest'), 'value' => money($loan->interest_amount)],
                    ['label' => t('loans.fees'), 'value' => money($loan->processing_fee)],
                    ['label' => t('loans.insuranceFee'), 'value' => money($loan->insurance_amount)],
                    ['label' => t('loans.penalty'), 'value' => money($loan->penalty_amount)],
                    ['label' => t('loans.totalRepayable'), 'value' => '<b>'.money($loan->total_amount).'</b>'],
                ]" />
            </x-card>
        </div>

        <x-card flush class="min-w-0">
            <x-tabs :current="$tab" :items="[
                ['key' => 'overview', 'label' => t('loans.tabs.overview')],
                ['key' => 'schedule', 'label' => t('loans.tabs.schedule'), 'count' => $loan->schedule->count()],
                ['key' => 'repayments', 'label' => t('loans.tabs.repayments'), 'count' => $loan->repayments->count()],
                ['key' => 'guarantors', 'label' => t('loans.tabs.guarantors'), 'count' => $loan->guarantors->count()],
            ]" />
            <div class="p-4">
                @if ($tab === 'overview')
                    <x-kv :items="[
                        ['label' => t('common.member'), 'value' => e($loan->member->full_name)],
                        ['label' => t('loans.product'), 'value' => e($loan->product->name)],
                        ['label' => t('loans.purpose'), 'value' => e($loan->purpose)],
                        ['label' => t('loans.period'), 'value' => $loan->period.' '.t('loans.months')],
                        ['label' => t('loans.frequency'), 'value' => t('loans.freq.'.$loan->repayment_frequency)],
                        ['label' => t('loans.interestMethodLabel'), 'value' => t('loans.method.'.$loan->interest_method)],
                        ['label' => t('loans.applicationDate'), 'value' => fdate($loan->application_date)],
                        ['label' => t('loans.disbursementDate'), 'value' => fdate($loan->disbursement_date)],
                        ['label' => t('loans.maturityDate'), 'value' => fdate($loan->maturity_date)],
                        ['label' => t('common.status'), 'value' => e(t('loans.status.'.$loan->status))],
                    ]" />
                @elseif ($tab === 'schedule')
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead><tr class="border-b border-neutral-200 text-left text-[11px] uppercase tracking-wide text-neutral-500">
                                <th class="px-3 py-2.5">#</th><th class="px-3 py-2.5">{{ t('loans.dueDate') }}</th>
                                <th class="px-3 py-2.5 text-right">{{ t('loans.principal') }}</th><th class="px-3 py-2.5 text-right">{{ t('loans.interest') }}</th>
                                <th class="px-3 py-2.5 text-right">{{ t('loans.amountDue') }}</th><th class="px-3 py-2.5 text-right">{{ t('loans.amountPaid') }}</th>
                                <th class="px-3 py-2.5">{{ t('common.status') }}</th>
                            </tr></thead>
                            <tbody class="divide-y divide-neutral-100">
                                @forelse ($loan->schedule as $r)
                                    <tr class="{{ $r->status === 'overdue' ? 'bg-red-50/60' : '' }}">
                                        <td class="px-3 py-2.5 text-[13px] text-neutral-500">{{ $r->installment_number }}</td>
                                        <td class="px-3 py-2.5 text-[13px]">{{ fdate($r->due_date) }}</td>
                                        <td class="px-3 py-2.5 text-right text-[13px] tabular-nums">{{ money($r->principal_due, symbol: false) }}</td>
                                        <td class="px-3 py-2.5 text-right text-[13px] tabular-nums">{{ money($r->interest_due, symbol: false) }}</td>
                                        <td class="px-3 py-2.5 text-right text-[13px] font-medium tabular-nums">{{ money($r->total_due, symbol: false) }}</td>
                                        <td class="px-3 py-2.5 text-right text-[13px] tabular-nums">{{ money($r->amount_paid, symbol: false) }}</td>
                                        <td class="px-3 py-2.5"><x-status :status="$r->status" :label="t('loans.scheduleStatus.'.$r->status)" /></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7"><x-empty :title="t('loans.noScheduleYet')" icon="calendar" /></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @elseif ($tab === 'repayments')
                    <x-mini-table :head="[t('common.date'), t('common.reference'), t('loans.principal'), t('loans.interest'), t('common.total'), t('payments.method')]"
                        :align="['','','right','right','right','']"
                        :rows="$loan->repayments->map(fn($r) => [fdate($r->payment_date), $r->reference, money($r->principal_paid), money($r->interest_paid), money($r->total_paid), t('payments.methods.'.$r->method)])" />
                @elseif ($tab === 'guarantors')
                    <table class="w-full text-sm">
                        <thead><tr class="border-b border-neutral-200 text-left text-[11px] uppercase tracking-wide text-neutral-500">
                            <th class="px-2 py-2.5">{{ t('guarantors.guarantor') }}</th>
                            <th class="px-2 py-2.5 text-right">{{ t('guarantors.guaranteedAmount') }}</th>
                            <th class="px-2 py-2.5">{{ t('common.status') }}</th>
                            <th></th>
                        </tr></thead>
                        <tbody class="divide-y divide-neutral-100">
                            @forelse ($loan->guarantors as $g)
                                <tr>
                                    <td class="px-2 py-2.5 text-[13px] font-medium">{{ $g->guarantorMember?->full_name ?? '—' }}</td>
                                    <td class="px-2 py-2.5 text-right">{{ money($g->guaranteed_amount) }}</td>
                                    <td class="px-2 py-2.5"><x-status :status="$g->status" /></td>
                                    <td class="px-2 py-2.5 text-right">
                                        @if ($g->status !== 'released')
                                            <form method="POST" action="{{ route('admin.guarantors.destroy', $g) }}">@csrf @method('DELETE')
                                                <button class="k-btn k-btn-outlined k-btn-sm">{{ t('guarantors.status.released') }}</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><x-empty :title="t('common.noData')" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                @endif
            </div>
        </x-card>
    </div>

    @if ($isRunning)
        <x-modal name="repay" :title="t('loans.recordRepayment')">
            <form method="POST" action="{{ route('admin.loans.repay', $loan) }}" class="grid gap-4" x-data="{ busy: false }" x-on:submit="busy = true">
                @csrf
                <p class="rounded-md bg-neutral-50 px-3 py-2 text-[13px] text-neutral-600">
                    {{ t('loans.outstanding') }}: <b class="text-neutral-900">{{ money($loan->outstanding_balance) }}</b>
                    @if ($nextDue) · {{ t('loans.nextPayment') }}: <b class="text-neutral-900">{{ money($nextDueAmount) }}</b> ({{ fdate($nextDue->due_date) }}) @endif
                </p>
                <x-field :label="t('common.amount')" name="amount">
                    <x-input type="number" name="amount" min="1" max="{{ $loan->outstanding_balance }}" step="1" required inputmode="numeric"
                             value="{{ old('amount', $nextDueAmount ?: $loan->outstanding_balance) }}" />
                </x-field>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field :label="t('payments.method')" name="method">
                        <x-select name="method">@foreach (['cash','mobile_money','bank'] as $m)<option value="{{ $m }}" @selected(old('method') === $m)>{{ t("payments.methods.$m") }}</option>@endforeach</x-select>
                    </x-field>
                    <x-field :label="t('common.date')" name="date">
                        <x-input type="date" name="date" max="{{ now()->toDateString() }}" value="{{ old('date', now()->toDateString()) }}" required />
                    </x-field>
                </div>
                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" class="k-btn k-btn-outlined" x-on:click="$dispatch('close-modal','repay')">{{ t('common.cancel') }}</button>
                    <button class="k-btn k-btn-primary" x-bind:disabled="busy">{{ t('loans.recordRepayment') }}</button>
                </div>
            </form>
        </x-modal>
        @if ($errors->hasAny(['amount', 'method', 'date']))
            <div x-data x-init="$nextTick(() => $dispatch('open-modal', 'repay'))"></div>
        @endif
    @endif

    @foreach ([
        'approve' => ['route' => 'admin.loans.approve', 'text' => t('loans.confirmApprove', ['amount' => money($loan->principal_amount), 'member' => $loan->member->full_name]), 'button' => t('common.approve'), 'variant' => 'primary'],
        'reject' => ['route' => 'admin.loans.reject', 'text' => t('loans.confirmReject', ['member' => $loan->member->full_name]), 'button' => t('common.reject'), 'variant' => 'danger'],
        'disburse' => ['route' => 'admin.loans.disburse', 'text' => t('loans.confirmDisburse', ['amount' => money($loan->principal_amount), 'member' => $loan->member->full_name]), 'button' => t('loans.disburse'), 'variant' => 'primary'],
    ] as $name => $action)
        <x-modal :name="$name" :title="$action['button']" size="sm">
            <form method="POST" action="{{ route($action['route'], $loan) }}" x-data="{ busy: false }" x-on:submit="busy = true">
                @csrf
                <p class="text-[13.5px] leading-relaxed text-neutral-700">{{ $action['text'] }}</p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="k-btn k-btn-outlined" x-on:click="$dispatch('close-modal','{{ $name }}')">{{ t('common.cancel') }}</button>
                    <button class="k-btn k-btn-{{ $action['variant'] }}" x-bind:disabled="busy">{{ $action['button'] }}</button>
                </div>
            </form>
        </x-modal>
    @endforeach
</x-layouts.app>

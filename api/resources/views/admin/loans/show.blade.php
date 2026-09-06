@php
    $title = $loan->loan_number;
    $workflow = ['submitted','under_review','approved','disbursed','active','completed'];
    $step = array_search($loan->status, $workflow, true);
    $canDecide = in_array($loan->status, ['submitted','under_review']);
@endphp
<x-layouts.app :title="$title">
    <x-page-header :title="$loan->loan_number" :subtitle="$loan->product->name.' · '.$loan->member->full_name" :back="route('admin.loans.index')" :backLabel="t('loans.title')">
        <x-slot:actions>
            @if ($canDecide)
                <form method="POST" action="{{ route('admin.loans.reject', $loan) }}">@csrf<button class="k-btn k-btn-danger">{{ t('common.reject') }}</button></form>
                <form method="POST" action="{{ route('admin.loans.approve', $loan) }}">@csrf<button class="k-btn k-btn-primary">{{ t('common.approve') }}</button></form>
            @elseif ($loan->status === 'approved')
                <form method="POST" action="{{ route('admin.loans.disburse', $loan) }}">@csrf<button class="k-btn k-btn-primary">{{ t('loans.disburse') }}</button></form>
            @elseif (in_array($loan->status, ['active','overdue','disbursed']))
                <x-btn x-data x-on:click="$dispatch('open-modal','repay')">{{ t('loans.recordRepayment') }}</x-btn>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-card class="mb-4">
        <div class="flex flex-wrap items-center gap-y-3">
            @foreach ($workflow as $i => $s)
                @php $done = $step !== false && $i <= $step; @endphp
                <div class="flex items-center">
                    <div class="flex flex-col items-center gap-1.5">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full border-2 text-[12px] font-semibold {{ $done ? 'border-primary-600 bg-primary-600 text-white' : 'border-neutral-300 bg-white text-neutral-400' }}">
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
            <x-stat :label="t('loans.outstanding')" :value="money($loan->outstanding_balance)" />
            <x-card>
                <x-progress :value="$loan->total_amount ? $loan->amount_paid / $loan->total_amount * 100 : 0" showLabel :tone="$loan->status==='overdue'?'danger':'tertiary'" />
                <p class="mt-2 text-[12px] text-neutral-500">{{ money($loan->amount_paid) }} / {{ money($loan->total_amount) }}</p>
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
                        ['label' => t('common.status'), 'value' => ucfirst(str_replace('_',' ',$loan->status))],
                    ]" />
                @elseif ($tab === 'schedule')
                    <x-mini-table :head="['#', t('loans.dueDate'), t('loans.principal'), t('loans.interest'), t('loans.amountDue'), t('loans.amountPaid'), t('common.status')]"
                        :align="['','','right','right','right','right','']"
                        :rows="$loan->schedule->map(fn($r) => [$r->installment_number, fdate($r->due_date), money($r->principal_due), money($r->interest_due), money($r->total_due), money($r->amount_paid), ucfirst($r->status)])" />
                @elseif ($tab === 'repayments')
                    <x-mini-table :head="[t('common.date'), t('common.reference'), t('loans.principal'), t('loans.interest'), t('common.total'), t('payments.method')]"
                        :align="['','','right','right','right','']"
                        :rows="$loan->repayments->map(fn($r) => [fdate($r->payment_date), $r->reference, money($r->principal_paid), money($r->interest_paid), money($r->total_paid), t('payments.methods.'.$r->method)])" />
                @elseif ($tab === 'guarantors')
                    <x-mini-table :head="[t('guarantors.guarantor'), t('guarantors.guaranteedAmount'), t('common.status'), '']"
                        :align="['','right','','right']" html
                        :rows="$loan->guarantors->map(fn($g) => [e($g->guarantorMember?->full_name), money($g->guaranteed_amount), ucfirst($g->status),
                            $g->status !== 'released' ? '<form method=POST action='.route('admin.guarantors.destroy',$g).'>'.csrf_field().method_field('DELETE').'<button class=\"k-btn k-btn-outlined k-btn-sm\">'.t('guarantors.status.released').'</button></form>' : ''])" />
                @endif
            </div>
        </x-card>
    </div>

    @if (in_array($loan->status, ['active','overdue','disbursed']))
        <x-modal name="repay" :title="t('loans.recordRepayment')">
            <form method="POST" action="{{ route('admin.loans.repay', $loan) }}" class="grid gap-4">
                @csrf
                <x-field :label="t('common.amount')"><x-input type="number" name="amount" value="{{ (int) round($loan->outstanding_balance / max(1, $loan->period)) }}" /></x-field>
                <x-field :label="t('payments.method')"><x-select name="method">@foreach (['mobile_money','bank','cash'] as $m)<option value="{{ $m }}">{{ t("payments.methods.$m") }}</option>@endforeach</x-select></x-field>
                <button class="k-btn k-btn-primary">{{ t('common.save') }}</button>
            </form>
        </x-modal>
    @endif
</x-layouts.app>

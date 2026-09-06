@php $title = $loan->loan_number; @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="$loan->loan_number" :subtitle="$loan->product->name" :back="route('member.loans')" :backLabel="t('nav.myLoans')" />
    <div class="grid gap-4 lg:grid-cols-[320px_1fr]">
        <div class="flex flex-col gap-4">
            <x-stat :label="t('member.outstanding')" :value="money($loan->outstanding_balance)" />
            <x-card>
                <x-progress :value="$loan->total_amount ? $loan->amount_paid / $loan->total_amount * 100 : 0" showLabel :tone="$loan->status==='overdue'?'danger':'tertiary'" />
                <p class="mt-2 text-[12px] text-neutral-500">{{ money($loan->amount_paid) }} / {{ money($loan->total_amount) }}</p>
            </x-card>
            <x-card>
                <x-card-header :title="t('common.summary')" />
                <x-kv :cols="1" :items="[
                    ['label' => t('loans.principal'), 'value' => money($loan->principal_amount)],
                    ['label' => t('loans.interest'), 'value' => money($loan->interest_amount)],
                    ['label' => t('loans.fees'), 'value' => money($loan->processing_fee + $loan->insurance_amount)],
                    ['label' => t('loans.totalRepayable'), 'value' => '<b>'.money($loan->total_amount).'</b>'],
                    ['label' => t('loans.disbursementDate'), 'value' => fdate($loan->disbursement_date)],
                    ['label' => t('loans.maturityDate'), 'value' => fdate($loan->maturity_date)],
                    ['label' => t('common.status'), 'value' => ucfirst(str_replace('_',' ',$loan->status))],
                ]" />
            </x-card>
        </div>
        <x-card flush class="min-w-0">
            <x-tabs :current="$tab" :items="[
                ['key'=>'schedule','label'=>t('loans.tabs.schedule'),'count'=>$loan->schedule->count()],
                ['key'=>'repayments','label'=>t('loans.tabs.repayments'),'count'=>$loan->repayments->count()],
                ['key'=>'guarantors','label'=>t('loans.tabs.guarantors'),'count'=>$loan->guarantors->count()],
            ]" />
            <div class="p-4">
                @if ($tab === 'schedule')
                    <x-mini-table :head="['#', t('loans.dueDate'), t('loans.amountDue'), t('loans.amountPaid'), t('common.status')]" :align="['','','right','right','']"
                        :rows="$loan->schedule->map(fn($r) => [$r->installment_number, fdate($r->due_date), money($r->total_due), money($r->amount_paid), ucfirst($r->status)])" />
                @elseif ($tab === 'repayments')
                    <x-mini-table :head="[t('common.date'), t('common.reference'), t('common.total'), t('payments.method')]" :align="['','','right','']"
                        :rows="$loan->repayments->map(fn($r) => [fdate($r->payment_date), $r->reference, money($r->total_paid), t('payments.methods.'.$r->method)])" />
                @else
                    <x-mini-table :head="[t('guarantors.guarantor'), t('guarantors.guaranteedAmount'), t('common.status')]" :align="['','right','']"
                        :rows="$loan->guarantors->map(fn($g) => [e($g->guarantorMember?->full_name), money($g->guaranteed_amount), ucfirst($g->status)])" />
                @endif
            </div>
        </x-card>
    </div>
</x-layouts.app>

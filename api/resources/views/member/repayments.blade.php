@php
    $title = t('nav.repayments');
    $paid = $history->sum('total_paid');
    $due = $upcoming->sum(fn ($r) => $r->total_due - $r->amount_paid);
@endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('nav.repayments')" :subtitle="t('loans.repaymentHistory')" />
    <div class="grid gap-4 sm:grid-cols-2">
        <x-stat tone="tertiary" :label="t('loans.repaymentHistory')" :value="money($paid, true)" />
        <x-stat tone="neutral" :label="t('dashboard.upcomingRepayments')" :value="money($due, true)" />
    </div>
    <x-card class="mt-4" flush>
        <x-card-header :title="t('dashboard.upcomingRepayments')" class="p-4" />
        <x-mini-table :head="[t('loans.loanNumber'), t('loans.installment'), t('loans.dueDate'), t('loans.amountDue'), t('common.status')]" :align="['','','','right','']"
            :rows="$upcoming->map(fn ($r) => [$r->loan?->loan_number, '#'.$r->installment_number, fdate($r->due_date), money($r->total_due - $r->amount_paid), ucfirst($r->status)])" />
    </x-card>
    <x-card class="mt-4" flush>
        <x-card-header :title="t('loans.repaymentHistory')" class="p-4" />
        <x-mini-table :head="[t('common.date'), t('loans.loanNumber'), t('common.reference'), t('loans.principal'), t('loans.interest'), t('common.total')]" :align="['','','','right','right','right']"
            :rows="$history->map(fn ($r) => [fdate($r->payment_date), $r->loan?->loan_number, $r->reference, money($r->principal_paid), money($r->interest_paid), money($r->total_paid)])" />
    </x-card>
</x-layouts.app>

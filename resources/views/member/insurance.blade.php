@php $title = t('nav.myInsurance'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('nav.myInsurance')" :subtitle="t('insurance.subtitle')" />
    @if (! $account)
        <x-card><x-empty :title="t('common.noData')" :hint="t('insurance.subtitle')" /></x-card>
    @else
        <div class="grid gap-4 sm:grid-cols-3">
            <x-stat :label="t('insurance.coverageAmount')" :value="money($account->coverage_amount, true)" />
            <x-stat tone="tertiary" :label="t('insurance.totalContributions')" :value="money($contributions->sum('amount'), true)" />
            <x-stat tone="secondary" :label="t('common.status')" :value="t('insurance.status.'.$account->status)" />
        </div>
        <x-card class="mt-4">
            <x-card-header :title="$account->plan_name" />
            <x-kv :items="[
                ['label' => t('insurance.monthlyContribution'), 'value' => money($account->monthly_contribution)],
                ['label' => t('insurance.coverageAmount'), 'value' => money($account->coverage_amount)],
                ['label' => t('insurance.startDate'), 'value' => fdate($account->start_date)],
                ['label' => t('insurance.endDate'), 'value' => fdate($account->end_date)],
            ]" />
        </x-card>
        <x-card class="mt-4" flush>
            <x-card-header :title="t('insurance.totalContributions')" class="p-4" />
            <x-mini-table :head="[t('reports.period'), t('common.amount'), t('common.reference'), t('common.date')]" :align="['','right','','']"
                :rows="$contributions->map(fn ($c) => [$c->period, money($c->amount), $c->reference, fdate($c->paid_on)])" />
        </x-card>
        <x-card class="mt-4" flush>
            <x-card-header :title="t('nav.claims')" class="p-4" />
            <x-mini-table :head="[t('insurance.claimNumber'), t('insurance.claimType'), t('insurance.amountRequested'), t('insurance.amountApproved'), t('common.status')]" :align="['','','right','right','']"
                :rows="$claims->map(fn ($c) => [$c->claim_number, $c->claim_type, money($c->amount_requested), $c->amount_approved ? money($c->amount_approved) : '—', ucfirst(str_replace('_',' ',$c->status))])" />
        </x-card>
    @endif
</x-layouts.app>

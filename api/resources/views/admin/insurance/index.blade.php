@php $title = t('insurance.title'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('insurance.title')" :subtitle="t('insurance.subtitle')">
        <x-slot:actions>
            <x-btn variant="outlined" icon="document-plus" x-data x-on:click="$dispatch('open-modal','claim')">{{ t('insurance.fileClaim') }}</x-btn>
            <x-btn icon="plus" x-data x-on:click="$dispatch('open-modal','contrib')">{{ t('insurance.recordContribution') }}</x-btn>
        </x-slot:actions>
    </x-page-header>
    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat tone="tertiary" :label="t('insurance.totalContributions')" :value="money($summary['contributions'], true)" />
        <x-stat tone="neutral" :label="t('insurance.totalClaims')" :value="money($summary['claimsPaid'], true)" />
        <x-stat tone="secondary" :label="t('insurance.activeCoverage')" :value="$summary['covered']" />
    </div>

    <x-card class="mt-4" flush>
        <x-tabs :current="$tab" :items="[['key'=>'accounts','label'=>t('savings.accounts'),'count'=>$accounts->total()],['key'=>'claims','label'=>t('nav.claims'),'count'=>$claims->total()]]" />
        <div class="p-4">
            @if ($tab === 'accounts')
                <x-mini-table :head="[t('common.member'), t('insurance.planName'), t('insurance.monthlyContribution'), t('insurance.coverageAmount'), t('insurance.totalContributions'), t('common.status'), '']"
                    :align="['','','right','right','right','','right']" html
                    :rows="$accounts->map(fn ($a) => [e($a->member->full_name), e($a->plan_name), money($a->monthly_contribution), money($a->coverage_amount), money($a->total_contributed ?? 0), ucfirst($a->status),
                        '<form method=POST action='.route('admin.insurance.accounts.destroy', $a).'>'.csrf_field().method_field('DELETE').'<button class=\"k-btn k-btn-outlined k-btn-sm\">'.t('common.cancel').'</button></form>'])" />
                @if ($accounts->hasPages())<div class="pt-3">{{ $accounts->links() }}</div>@endif
            @else
                <table class="w-full text-sm">
                    <thead><tr class="border-b border-neutral-200 text-left text-[11px] uppercase tracking-wide text-neutral-500">
                        <th class="px-2 py-2.5">{{ t('insurance.claimNumber') }}</th><th class="px-2 py-2.5">{{ t('common.member') }}</th>
                        <th class="px-2 py-2.5">{{ t('insurance.claimType') }}</th><th class="px-2 py-2.5 text-right">{{ t('insurance.amountRequested') }}</th>
                        <th class="px-2 py-2.5">{{ t('common.status') }}</th><th></th></tr></thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach ($claims as $c)
                            <tr>
                                <td class="px-2 py-2.5 font-medium text-primary-700">{{ $c->claim_number }}</td>
                                <td class="px-2 py-2.5 text-[13px]">{{ $c->member->full_name }}</td>
                                <td class="px-2 py-2.5"><x-badge tone="neutral">{{ $c->claim_type }}</x-badge></td>
                                <td class="px-2 py-2.5 text-right">{{ money($c->amount_requested) }}</td>
                                <td class="px-2 py-2.5"><x-status :status="$c->status" :label="t('insurance.claimStatus.'.$c->status)" /></td>
                                <td class="px-2 py-2.5 text-right">
                                    @if (in_array($c->status, ['submitted','under_review']))
                                        <form method="POST" action="{{ route('admin.insurance.claims.decide', $c) }}" class="inline"><input type="hidden" name="decision" value="approve">@csrf<button class="k-btn k-btn-primary k-btn-sm">{{ t('common.approve') }}</button></form>
                                    @endif
                                    <x-row-actions :delete="route('admin.insurance.claims.destroy', $c)" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if ($claims->hasPages())<div class="pt-3">{{ $claims->links() }}</div>@endif
            @endif
        </div>
    </x-card>

    <x-modal name="contrib" :title="t('insurance.recordContribution')">
        <form method="POST" action="{{ route('admin.insurance.contribute') }}" class="grid gap-4">@csrf
            <x-field :label="t('common.member')"><x-select name="insurance_account_id">@foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->member->full_name }}</option>@endforeach</x-select></x-field>
            <x-field :label="t('common.amount')"><x-input type="number" name="amount" value="20000" /></x-field>
            <x-field :label="t('reports.period')"><x-input type="month" name="period" value="{{ now()->format('Y-m') }}" /></x-field>
            <button class="k-btn k-btn-primary">{{ t('common.submit') }}</button>
        </form>
    </x-modal>
    <x-modal name="claim" :title="t('insurance.fileClaim')">
        <form method="POST" action="{{ route('admin.insurance.claims.store') }}" class="grid gap-4">@csrf
            <x-field :label="t('common.member')"><x-select name="member_id">@foreach ($accounts as $a)<option value="{{ $a->member_id }}">{{ $a->member->full_name }}</option>@endforeach</x-select></x-field>
            <x-field :label="t('insurance.claimType')"><x-select name="claim_type"><option>Medical</option><option>Funeral</option><option>Property loss</option><option>Disability</option></x-select></x-field>
            <x-field :label="t('insurance.amountRequested')"><x-input type="number" name="amount_requested" /></x-field>
            <x-field :label="t('common.description')"><x-input name="description" /></x-field>
            <button class="k-btn k-btn-primary">{{ t('common.submit') }}</button>
        </form>
    </x-modal>
</x-layouts.app>

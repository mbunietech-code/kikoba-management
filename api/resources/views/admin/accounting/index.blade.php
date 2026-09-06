@php
    $title = t('accounting.title');
    $tint = ['asset'=>'primary','liability'=>'info','equity'=>'purple','revenue'=>'success','expense'=>'danger'];
    $assets = $accounts->where('account_type','asset')->sum('balance');
    $liab = $accounts->where('account_type','liability')->sum('balance');
@endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('accounting.title')" :subtitle="t('accounting.subtitle')" />
    <div class="grid gap-4 sm:grid-cols-4">
        <x-stat :label="t('accounting.types.asset')" :value="money($assets, true)" />
        <x-stat tone="secondary" :label="t('accounting.types.liability')" :value="money($liab, true)" />
        <x-stat tone="tertiary" :label="t('accounting.totalDebit')" :value="money($totalDebit, true)" />
        <x-stat tone="neutral" :label="t('accounting.totalCredit')" :value="money($totalCredit, true)" :hint="$totalDebit === $totalCredit ? t('accounting.balanced') : t('accounting.unbalanced')" />
    </div>

    <x-card class="mt-4" flush>
        <x-tabs :current="$tab" :items="[
            ['key'=>'coa','label'=>t('accounting.chartOfAccounts'),'count'=>$accounts->count()],
            ['key'=>'journal','label'=>t('accounting.journalEntries'),'count'=>$journal->total()],
            ['key'=>'trial','label'=>t('accounting.trialBalance')],
        ]" />
        <div class="p-4">
            @if ($tab === 'coa')
                <div class="mb-3 flex justify-end">
                    <x-btn size="sm" icon="plus" x-data x-on:click="$dispatch('open-modal','acc')">{{ t('common.add') }}</x-btn>
                </div>
                <table class="w-full text-sm">
                    <thead><tr class="border-b border-neutral-200 text-left text-[11px] uppercase tracking-wide text-neutral-500">
                        <th class="px-2 py-2.5">{{ t('accounting.accountCode') }}</th><th class="px-2 py-2.5">{{ t('accounting.accountName') }}</th>
                        <th class="px-2 py-2.5">{{ t('accounting.accountType') }}</th><th class="px-2 py-2.5 text-right">{{ t('common.balance') }}</th><th></th></tr></thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach ($accounts as $a)
                            <tr>
                                <td class="px-2 py-2.5 font-mono text-[13px] text-neutral-600">{{ $a->account_code }}</td>
                                <td class="px-2 py-2.5 font-medium">{{ $a->name }}</td>
                                <td class="px-2 py-2.5"><x-badge :tone="$tint[$a->account_type]">{{ t('accounting.types.'.$a->account_type) }}</x-badge></td>
                                <td class="px-2 py-2.5 text-right font-medium tabular-nums">{{ money($a->balance) }}</td>
                                <td class="px-2 py-2.5 text-right"><x-row-actions :delete="route('admin.accounting.accounts.destroy', $a)" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @elseif ($tab === 'journal')
                <table class="w-full text-sm">
                    <thead><tr class="border-b border-neutral-200 text-left text-[11px] uppercase tracking-wide text-neutral-500">
                        <th class="px-2 py-2.5">{{ t('common.reference') }}</th><th class="px-2 py-2.5">{{ t('common.description') }}</th>
                        <th class="px-2 py-2.5">{{ t('accounting.entryDate') }}</th><th class="px-2 py-2.5 text-right">{{ t('common.amount') }}</th></tr></thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach ($journal as $j)
                            <tr class="cursor-pointer hover:bg-neutral-50" onclick="window.location='{{ route('admin.accounting.journal.show', $j) }}'">
                                <td class="px-2 py-2.5 font-mono text-[12px] text-primary-700">{{ $j->reference }}</td>
                                <td class="px-2 py-2.5 text-[13px]">{{ $j->description }}</td>
                                <td class="px-2 py-2.5 text-[13px] text-neutral-500">{{ fdate($j->entry_date) }}</td>
                                <td class="px-2 py-2.5 text-right font-medium">{{ money($j->lines->sum('debit')) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if ($journal->hasPages())<div class="pt-3">{{ $journal->links() }}</div>@endif
            @else
                <table class="w-full text-sm">
                    <thead><tr class="border-b border-neutral-200 text-left text-[11px] uppercase tracking-wide text-neutral-500">
                        <th class="py-2">{{ t('accounting.accountName') }}</th><th class="py-2 text-right">{{ t('accounting.debit') }}</th><th class="py-2 text-right">{{ t('accounting.credit') }}</th></tr></thead>
                    <tbody>
                        @foreach ($accounts as $a)
                            @php $isD = in_array($a->account_type, ['asset','expense']); @endphp
                            <tr class="border-b border-neutral-100"><td class="py-2.5">{{ $a->account_code }} · {{ $a->name }}</td>
                                <td class="py-2.5 text-right tabular-nums">{{ $isD ? money($a->balance) : '—' }}</td>
                                <td class="py-2.5 text-right tabular-nums">{{ ! $isD ? money($a->balance) : '—' }}</td></tr>
                        @endforeach
                        <tr class="font-semibold"><td class="py-3">{{ t('common.total') }}</td>
                            <td class="py-3 text-right tabular-nums">{{ money($accounts->whereIn('account_type', ['asset','expense'])->sum('balance')) }}</td>
                            <td class="py-3 text-right tabular-nums">{{ money($accounts->whereNotIn('account_type', ['asset','expense'])->sum('balance')) }}</td></tr>
                    </tbody>
                </table>
            @endif
        </div>
    </x-card>

    <x-modal name="acc" :title="t('accounting.chartOfAccounts')">
        <form method="POST" action="{{ route('admin.accounting.accounts.store') }}" class="grid gap-4">@csrf
            <div class="grid grid-cols-2 gap-4">
                <x-field :label="t('accounting.accountCode')"><x-input name="account_code" /></x-field>
                <x-field :label="t('accounting.accountType')"><x-select name="account_type">@foreach (['asset','liability','equity','revenue','expense'] as $ty)<option value="{{ $ty }}">{{ t("accounting.types.$ty") }}</option>@endforeach</x-select></x-field>
            </div>
            <x-field :label="t('accounting.accountName')"><x-input name="name" /></x-field>
            <button class="k-btn k-btn-primary">{{ t('common.save') }}</button>
        </form>
    </x-modal>
</x-layouts.app>

@php $title = t('nav.transactions'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('nav.transactions')" :subtitle="t('transactions.subtitle')" />
    @include('partials.toolbar', ['filters' => [['name' => 'type', 'value' => request('type'), 'options' => ['' => t('common.all')] + $types->mapWithKeys(fn ($x) => [$x => t('transactions.types.'.strtolower($x))])->all()]]])
    <x-card flush>
        <table class="w-full text-sm">
            <thead><tr class="border-b border-neutral-200 text-left text-[11px] uppercase tracking-wide text-neutral-500">
                <th class="px-4 py-3">{{ t('transactions.txnRef') }}</th><th class="px-4 py-3">{{ t('common.type') }}</th>
                <th class="px-4 py-3 text-right">{{ t('common.amount') }}</th><th class="px-4 py-3">{{ t('common.date') }}</th>
                <th class="px-4 py-3">{{ t('common.status') }}</th></tr></thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse ($transactions as $tx)
                    <tr>
                        <td class="px-4 py-3 font-mono text-[12px] text-neutral-500">{{ $tx->transaction_reference }}</td>
                        <td class="px-4 py-3 text-[13px]">{{ t('transactions.types.'.strtolower($tx->type)) }}</td>
                        <td class="px-4 py-3 text-right font-medium">{{ money($tx->amount) }}</td>
                        <td class="px-4 py-3 text-[13px] text-neutral-500">{{ fdate($tx->created_at, 'datetime') }}</td>
                        <td class="px-4 py-3"><x-status :status="$tx->status" /></td>
                    </tr>
                @empty<tr><td colspan="5"><x-empty :title="t('common.noData')" /></td></tr>@endforelse
            </tbody>
        </table>
        @if ($transactions->hasPages())<div class="border-t border-neutral-100 px-4 py-3">{{ $transactions->links() }}</div>@endif
    </x-card>
</x-layouts.app>

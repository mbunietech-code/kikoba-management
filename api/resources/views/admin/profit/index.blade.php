@php $title = t('profit.title'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('profit.title')" :subtitle="t('profit.subtitle')" />
    <x-card flush>
        <table class="w-full text-sm">
            <thead><tr class="border-b border-neutral-200 text-left text-[11px] uppercase tracking-wide text-neutral-500">
                <th class="px-4 py-3">{{ t('reports.period') }}</th><th class="px-4 py-3 text-right">{{ t('profit.totalProfit') }}</th>
                <th class="px-4 py-3 text-right">{{ t('profit.reservedAmount') }}</th><th class="px-4 py-3 text-right">{{ t('profit.distributableProfit') }}</th>
                <th class="px-4 py-3">{{ t('common.status') }}</th><th></th></tr></thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse ($distributions as $d)
                    <tr class="cursor-pointer hover:bg-neutral-50" onclick="window.location='{{ route('admin.profit.show', $d) }}'">
                        <td class="px-4 py-3 text-[13px]">{{ fdate($d->period_start, 'short') }} – {{ fdate($d->period_end, 'short') }}</td>
                        <td class="px-4 py-3 text-right">{{ money($d->total_profit) }}</td>
                        <td class="px-4 py-3 text-right">{{ money($d->reserved_amount) }}</td>
                        <td class="px-4 py-3 text-right font-medium">{{ money($d->distributable_profit) }}</td>
                        <td class="px-4 py-3"><x-status :status="$d->status" :label="t('profit.status.'.$d->status)" /></td>
                        <td class="px-4 py-3 text-right">@if (! in_array($d->status, ['approved','distributed']))<x-row-actions :delete="route('admin.profit.destroy', $d)" />@endif</td>
                    </tr>
                @empty<tr><td colspan="6"><x-empty :title="t('common.noData')" /></td></tr>@endforelse
            </tbody>
        </table>
    </x-card>
</x-layouts.app>

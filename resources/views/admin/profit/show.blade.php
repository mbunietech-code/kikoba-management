@php
    $title = t('profit.title');
    $paidSoFar = $distribution->allocations->where('status', 'paid')->sum('amount');
@endphp
<x-layouts.app :title="$title">
    <x-page-header :title="fdate($distribution->period_start).' – '.fdate($distribution->period_end)"
                   :subtitle="t('profit.basis'.ucfirst($distribution->basis)).' · '.t('profit.status.'.$distribution->status)"
                   :back="route('admin.profit.index')" :backLabel="t('profit.title')">
        <x-slot:actions>
            @can('profit.manage')
                @if ($distribution->status === 'calculated')
                    <x-row-actions :delete="route('admin.profit.destroy', $distribution)" :deleteLabel="t('profit.discard')" :confirm="t('profit.discardConfirm')" />
                    <x-btn x-data x-on:click="$dispatch('open-modal','approve')">{{ t('common.approve') }}</x-btn>
                @elseif ($distribution->status === 'approved')
                    <x-btn icon="banknotes" x-data x-on:click="$dispatch('open-modal','payout')">{{ t('profit.payOut') }}</x-btn>
                @endif
            @endcan
        </x-slot:actions>
    </x-page-header>

    @if ($distribution->status === 'calculated')
        <div class="mb-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-[13.5px] text-amber-900">{{ t('profit.reviewBanner') }}</div>
    @elseif ($distribution->status === 'distributed')
        <div class="mb-4 rounded-md border border-neutral-200 bg-neutral-50 px-4 py-3 text-[13.5px] text-neutral-700">{{ t('profit.distributedBanner', ['date' => fdate($distribution->distribution_date), 'amount' => money($paidSoFar)]) }}</div>
    @endif

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat :label="t('profit.totalProfit')" :value="money($distribution->total_profit)" />
        <x-stat :label="t('profit.reservedAmount')" :value="money($distribution->reserved_amount)" />
        <x-stat :label="t('profit.distributableProfit')" :value="money($distribution->distributable_profit)" :hint="t('profit.membersCount', ['count' => $distribution->allocations->where('amount', '>', 0)->count()])" />
    </div>
    <x-card class="mt-4" flush>
        <table class="w-full text-sm">
            <thead><tr class="border-b border-neutral-200 text-left text-[11px] uppercase tracking-wide text-neutral-500">
                <th class="px-4 py-3">{{ t('common.member') }}</th>
                <th class="px-4 py-3 text-right">{{ t('profit.sharePercent') }}</th>
                <th class="px-4 py-3 text-right">{{ t('profit.allocation') }}</th>
                <th class="px-4 py-3">{{ t('common.status') }}</th>
            </tr></thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse ($distribution->allocations as $a)
                    <tr>
                        <td class="px-4 py-3 text-[13.5px]"><span class="font-medium text-neutral-800">{{ $a->member->full_name }}</span> <span class="text-[12px] text-neutral-400">{{ $a->member->member_number }}</span></td>
                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-neutral-600">{{ number_format($a->percentage, 2) }}%</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right font-medium tabular-nums">{{ money($a->amount) }}</td>
                        <td class="px-4 py-3"><x-status :status="$a->status === 'paid' ? 'paid' : 'pending'" :label="t('profit.allocationStatus.'.($a->status === 'paid' ? 'paid' : 'pending'))" /></td>
                    </tr>
                @empty
                    <tr><td colspan="4"><x-empty :title="t('common.noData')" /></td></tr>
                @endforelse
            </tbody>
            @if ($distribution->allocations->isNotEmpty())
                <tfoot><tr class="border-t border-neutral-200 font-semibold">
                    <td class="px-4 py-3 text-[13.5px]">{{ t('common.total') }}</td>
                    <td class="px-4 py-3 text-right tabular-nums">100%</td>
                    <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ money($distribution->allocations->sum('amount')) }}</td>
                    <td></td>
                </tr></tfoot>
            @endif
        </table>
    </x-card>

    @can('profit.manage')
        <x-modal name="approve" :title="t('common.approve')" size="sm">
            <form method="POST" action="{{ route('admin.profit.approve', $distribution) }}">@csrf
                <p class="text-[13.5px] leading-relaxed text-neutral-700">{{ t('profit.approveConfirm', ['amount' => money($distribution->distributable_profit)]) }}</p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="k-btn k-btn-outlined" x-on:click="$dispatch('close-modal','approve')">{{ t('common.cancel') }}</button>
                    <button class="k-btn k-btn-primary">{{ t('common.approve') }}</button>
                </div>
            </form>
        </x-modal>
        <x-modal name="payout" :title="t('profit.payOut')">
            <form method="POST" action="{{ route('admin.profit.distribute', $distribution) }}" x-data="{ busy: false }" x-on:submit="busy = true">@csrf
                <p class="text-[13.5px] text-neutral-700">{{ t('profit.payOutIntro', ['amount' => money($distribution->distributable_profit)]) }}</p>
                <div class="mt-3 grid gap-2">
                    <label class="flex cursor-pointer gap-2.5 rounded-md border border-neutral-200 p-3 has-[:checked]:border-primary-600 has-[:checked]:bg-primary-50/50">
                        <input type="radio" name="method" value="savings" checked class="mt-0.5 accent-[var(--color-primary-800)]">
                        <span><span class="block text-[13.5px] font-medium">{{ t('profit.toSavings') }}</span><span class="block text-[12px] text-neutral-500">{{ t('profit.toSavingsHint') }}</span></span>
                    </label>
                    <label class="flex cursor-pointer gap-2.5 rounded-md border border-neutral-200 p-3 has-[:checked]:border-primary-600 has-[:checked]:bg-primary-50/50">
                        <input type="radio" name="method" value="cash" class="mt-0.5 accent-[var(--color-primary-800)]">
                        <span><span class="block text-[13.5px] font-medium">{{ t('profit.inCash') }}</span><span class="block text-[12px] text-neutral-500">{{ t('profit.inCashHint') }}</span></span>
                    </label>
                </div>
                <p class="mt-3 text-[12.5px] text-neutral-500">{{ t('profit.payOutFinal') }}</p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="k-btn k-btn-outlined" x-on:click="$dispatch('close-modal','payout')">{{ t('common.cancel') }}</button>
                    <button class="k-btn k-btn-primary" x-bind:disabled="busy">{{ t('profit.payOut') }}</button>
                </div>
            </form>
        </x-modal>
    @endcan
</x-layouts.app>

@php $title = t('loans.title'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('loans.title')" :subtitle="t('loans.subtitle')">
        <x-slot:actions><x-btn variant="outlined" :href="route('admin.products.index')" icon="rectangle-stack">{{ t('nav.loanProducts') }}</x-btn></x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 lg:grid-cols-[1fr_1fr_1fr_280px]">
        <x-stat :label="t('dashboard.totalLoans')" :value="money($summary['disbursed'], true)" />
        <x-stat tone="neutral" :label="t('dashboard.outstandingLoans')" :value="money($summary['outstanding'], true)" />
        <x-stat tone="neutral" :label="t('nav.overdue')" :value="money($summary['overdue'], true)" />
        <x-card>
            <x-card-header :title="t('dashboard.loanStatus')" />
            <div class="h-44">
                <canvas x-chart="{ type: 'doughnut', labels: @js($summary['byStatus']->keys()->map(fn($k) => t('loans.status.'.$k))->values()), data: @js($summary['byStatus']->values()) }"></canvas>
            </div>
        </x-card>
    </div>

    <x-card class="mt-4" flush>
        <x-tabs :current="$tab" :items="collect(['all','applications','active','overdue','completed'])->map(fn($k) => ['key' => $k, 'label' => $k === 'all' ? t('common.all') : t('nav.'.$k)])->all()" />
        <div class="p-3">@include('partials.toolbar')</div>
        <table class="w-full text-sm">
            <thead><tr class="border-b border-neutral-200 text-left text-[11px] uppercase tracking-wide text-neutral-500">
                <th class="px-4 py-3">{{ t('loans.loanNumber') }}</th><th class="px-4 py-3">{{ t('common.member') }}</th>
                <th class="px-4 py-3">{{ t('loans.product') }}</th><th class="px-4 py-3 text-right">{{ t('loans.principal') }}</th>
                <th class="px-4 py-3">{{ t('loans.amountPaid') }}</th><th class="px-4 py-3 text-right">{{ t('loans.outstanding') }}</th>
                <th class="px-4 py-3">{{ t('common.status') }}</th></tr></thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse ($loans as $l)
                    <tr class="cursor-pointer hover:bg-neutral-50" onclick="window.location='{{ route('admin.loans.show', $l) }}'">
                        <td class="px-4 py-3 font-medium text-primary-700">{{ $l->loan_number }}</td>
                        <td class="px-4 py-3"><div class="flex items-center gap-2"><x-avatar :name="$l->member->full_name" :color="$l->member->avatar_color" size="xs" /><span class="text-[13px]">{{ $l->member->full_name }}</span></div></td>
                        <td class="px-4 py-3 text-[13px]">{{ $l->product->name }}</td>
                        <td class="px-4 py-3 text-right">{{ money($l->principal_amount) }}</td>
                        <td class="px-4 py-3"><div class="w-28"><x-progress :value="$l->total_amount ? $l->amount_paid / $l->total_amount * 100 : 0" :tone="$l->status==='overdue'?'danger':'primary'" showLabel /></div></td>
                        <td class="px-4 py-3 text-right font-medium">{{ money($l->outstanding_balance) }}</td>
                        <td class="px-4 py-3"><x-status :status="$l->status" :label="t('loans.status.'.$l->status)" /></td>
                    </tr>
                @empty<tr><td colspan="7"><x-empty :title="t('common.noData')" /></td></tr>@endforelse
            </tbody>
        </table>
        @if ($loans->hasPages())<div class="border-t border-neutral-100 px-4 py-3">{{ $loans->links() }}</div>@endif
    </x-card>
</x-layouts.app>

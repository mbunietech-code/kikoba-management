@php $title = t('guarantors.title'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('guarantors.title')" :subtitle="t('guarantors.subtitle')" />
    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat :label="t('guarantors.title')" :value="$guarantors->total()" icon="shield-check" />
        <x-stat tone="neutral" :label="t('guarantors.status.pending')" :value="$guarantors->getCollection()->where('status','pending')->count()" />
        <x-stat tone="secondary" :label="t('guarantors.guaranteedAmount')" :value="money($guarantors->getCollection()->where('status','approved')->sum('guaranteed_amount'), true)" />
    </div>
    <div class="mt-4">
        @include('partials.toolbar', ['filters' => [['name' => 'status', 'value' => request('status'), 'options' => ['' => t('common.all')] + collect(['pending','approved','rejected','released'])->mapWithKeys(fn ($s) => [$s => t("guarantors.status.$s")])->all()]]])
        <x-card flush>
            <table class="w-full text-sm">
                <thead><tr class="border-b border-neutral-200 text-left text-[11px] uppercase tracking-wide text-neutral-500">
                    <th class="px-4 py-3">{{ t('loans.loanNumber') }}</th><th class="px-4 py-3">{{ t('guarantors.borrower') }}</th>
                    <th class="px-4 py-3">{{ t('guarantors.guarantor') }}</th><th class="px-4 py-3 text-right">{{ t('guarantors.guaranteedAmount') }}</th>
                    <th class="px-4 py-3">{{ t('common.status') }}</th><th></th></tr></thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse ($guarantors as $g)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-4 py-3"><a href="{{ route('admin.loans.show', $g->loan) }}" class="font-medium text-primary-700">{{ $g->loan_number }}</a></td>
                            <td class="px-4 py-3 text-[13px]">{{ $g->borrower?->full_name }}</td>
                            <td class="px-4 py-3 text-[13px]">{{ $g->guarantorMember?->full_name }}</td>
                            <td class="px-4 py-3 text-right">{{ money($g->guaranteed_amount) }}</td>
                            <td class="px-4 py-3"><x-status :status="$g->status" :label="t('guarantors.status.'.$g->status)" /></td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    @if ($g->status === 'pending')<form method="POST" action="{{ route('admin.guarantors.verify', $g) }}">@csrf<button class="k-btn k-btn-primary k-btn-sm">{{ t('guarantors.verify') }}</button></form>@endif
                                    @if ($g->status !== 'released')<x-row-actions :delete="route('admin.guarantors.destroy', $g)" :deleteLabel="t('guarantors.status.released')" />@endif
                                </div>
                            </td>
                        </tr>
                    @empty<tr><td colspan="6"><x-empty :title="t('common.noData')" /></td></tr>@endforelse
                </tbody>
            </table>
            @if ($guarantors->hasPages())<div class="border-t border-neutral-100 px-4 py-3">{{ $guarantors->links() }}</div>@endif
        </x-card>
    </div>
</x-layouts.app>

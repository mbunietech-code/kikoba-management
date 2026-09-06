@php $title = t('payments.title'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('payments.title')" :subtitle="t('payments.subtitle')" />
    <div class="grid gap-4 sm:grid-cols-2">
        <x-stat tone="tertiary" :label="t('payments.status.successful')" :value="money($summary['successful'], true)" />
        <x-stat tone="neutral" :label="t('payments.status.pending')" :value="$summary['pending']" />
    </div>
    <div class="mt-4">
        @include('partials.toolbar', ['filters' => [
            ['name' => 'status', 'value' => request('status'), 'options' => ['' => t('common.all')] + collect(['pending','successful','failed','reversed'])->mapWithKeys(fn ($s) => [$s => t("payments.status.$s")])->all()],
            ['name' => 'method', 'value' => request('method'), 'options' => ['' => t('common.all')] + collect(['mobile_money','bank','card','cash','manual'])->mapWithKeys(fn ($m) => [$m => t("payments.methods.$m")])->all()],
        ]])
        <x-card flush>
            <table class="w-full text-sm">
                <thead><tr class="border-b border-neutral-200 text-left text-[11px] uppercase tracking-wide text-neutral-500">
                    <th class="px-4 py-3">{{ t('payments.internalRef') }}</th><th class="px-4 py-3">{{ t('common.member') }}</th>
                    <th class="px-4 py-3">{{ t('common.description') }}</th><th class="px-4 py-3">{{ t('payments.method') }}</th>
                    <th class="px-4 py-3 text-right">{{ t('common.amount') }}</th><th class="px-4 py-3">{{ t('common.status') }}</th><th></th></tr></thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse ($payments as $p)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-4 py-3 font-mono text-[12px] text-neutral-500">{{ $p->internal_reference }}</td>
                            <td class="px-4 py-3 text-[13px]">{{ $p->member?->full_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-[13px]">{{ $p->purpose }}</td>
                            <td class="px-4 py-3"><x-badge tone="info">{{ t('payments.methods.'.$p->payment_method) }}</x-badge></td>
                            <td class="px-4 py-3 text-right font-medium">{{ money($p->amount) }}</td>
                            <td class="px-4 py-3"><x-status :status="$p->status" /></td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    @if ($p->status === 'pending')<form method="POST" action="{{ route('admin.payments.verify', $p) }}">@csrf<button class="k-btn k-btn-primary k-btn-sm">{{ t('payments.verify') }}</button></form>@endif
                                    @if (in_array($p->status, ['successful','pending']))<x-row-actions :delete="route('admin.payments.reverse', $p)" :deleteLabel="t('common.reverse')" />@endif
                                </div>
                            </td>
                        </tr>
                    @empty<tr><td colspan="7"><x-empty :title="t('common.noData')" /></td></tr>@endforelse
                </tbody>
            </table>
            @if ($payments->hasPages())<div class="border-t border-neutral-100 px-4 py-3">{{ $payments->links() }}</div>@endif
        </x-card>
    </div>
</x-layouts.app>

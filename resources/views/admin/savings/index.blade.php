@php $title = t('savings.title'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('savings.title')" :subtitle="t('savings.subtitle')">
        <x-slot:actions>
            @can('savings.withdraw')<x-btn variant="outlined" x-data x-on:click="$dispatch('open-modal','wd')">{{ t('savings.withdraw') }}</x-btn>@endcan
            @can('savings.deposit')<x-btn icon="plus" x-data x-on:click="$dispatch('open-modal','dep')">{{ t('savings.deposit') }}</x-btn>@endcan
        </x-slot:actions>
    </x-page-header>
    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat tone="tertiary" :label="t('savings.totalDeposits')" :value="money($summary['deposits'])" />
        <x-stat tone="neutral" :label="t('savings.totalWithdrawals')" :value="money($summary['withdrawals'])" />
        <x-stat tone="secondary" :label="t('savings.netSavings')" :value="money($summary['net'])" />
    </div>
    <div class="mt-4">
        @include('partials.toolbar')
        <x-card flush>
            <table class="w-full text-sm">
                <thead><tr class="border-b border-neutral-200 text-left text-[11px] uppercase tracking-wide text-neutral-500">
                    <th class="px-4 py-3">{{ t('common.member') }}</th><th class="px-4 py-3">{{ t('savings.accountNumber') }}</th>
                    <th class="px-4 py-3">{{ t('common.created') }}</th><th class="px-4 py-3 text-right">{{ t('common.balance') }}</th></tr></thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse ($accounts as $a)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-4 py-3"><div class="flex items-center gap-2.5"><x-avatar :name="$a->member->full_name" :color="$a->member->avatar_color" size="sm" /><a href="{{ route('admin.savings.show', $a) }}" class="text-[13px] font-medium text-neutral-800 hover:text-primary-700 hover:underline">{{ $a->member->full_name }}</a></div></td>
                            <td class="px-4 py-3 font-mono text-[12px] text-neutral-500">{{ $a->account_number }}</td>
                            <td class="px-4 py-3 text-[13px] text-neutral-500">{{ fdate($a->opened_at) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums">{{ money($a->balance) }}</td>
                        </tr>
                    @empty<tr><td colspan="4"><x-empty :title="t('common.noData')" /></td></tr>@endforelse
                </tbody>
            </table>
            @if ($accounts->hasPages())<div class="border-t border-neutral-100 px-4 py-3">{{ $accounts->links() }}</div>@endif
        </x-card>
    </div>
    @include('admin.savings._move-modals')
</x-layouts.app>

@php $title = t('savings.title'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('savings.title')" :subtitle="t('savings.subtitle')">
        <x-slot:actions>
            <x-btn variant="outlined" icon="arrow-up-tray" x-data x-on:click="$dispatch('open-modal','wd')">{{ t('savings.withdraw') }}</x-btn>
            <x-btn icon="arrow-down-tray" x-data x-on:click="$dispatch('open-modal','dep')">{{ t('savings.deposit') }}</x-btn>
        </x-slot:actions>
    </x-page-header>
    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat tone="tertiary" :label="t('savings.totalDeposits')" :value="money($summary['deposits'], true)" />
        <x-stat tone="neutral" :label="t('savings.totalWithdrawals')" :value="money($summary['withdrawals'], true)" />
        <x-stat tone="secondary" :label="t('savings.netSavings')" :value="money($summary['net'], true)" />
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
                        <tr class="cursor-pointer hover:bg-neutral-50" onclick="window.location='{{ route('admin.savings.show',$a) }}'">
                            <td class="px-4 py-3"><div class="flex items-center gap-2.5"><x-avatar :name="$a->member->full_name" :color="$a->member->avatar_color" size="sm" /><span class="text-[13px] font-medium">{{ $a->member->full_name }}</span></div></td>
                            <td class="px-4 py-3 font-mono text-[12px] text-neutral-500">{{ $a->account_number }}</td>
                            <td class="px-4 py-3 text-[13px] text-neutral-500">{{ fdate($a->opened_at) }}</td>
                            <td class="px-4 py-3 text-right font-semibold">{{ money($a->balance) }}</td>
                        </tr>
                    @empty<tr><td colspan="4"><x-empty :title="t('common.noData')" /></td></tr>@endforelse
                </tbody>
            </table>
            @if ($accounts->hasPages())<div class="border-t border-neutral-100 px-4 py-3">{{ $accounts->links() }}</div>@endif
        </x-card>
    </div>
    @foreach (['dep' => ['savings.recordDeposit','deposit'], 'wd' => ['savings.recordWithdrawal','withdraw']] as $k => $m)
        <x-modal :name="$k" :title="t($m[0])">
            <form method="POST" action="{{ route('admin.savings.'.$m[1]) }}" class="grid gap-4">
                @csrf
                <x-field :label="t('savings.accountNumber')"><x-select name="account_id">@foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->member->full_name }} · {{ $a->account_number }}</option>@endforeach</x-select></x-field>
                <x-field :label="t('common.amount')"><x-input type="number" name="amount" /></x-field>
                <x-field :label="t('common.reference')" :hint="t('common.optional')"><x-input name="reference" /></x-field>
                <button class="k-btn k-btn-primary">{{ t('common.save') }}</button>
            </form>
        </x-modal>
    @endforeach
</x-layouts.app>

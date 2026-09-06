@php $title = t('shares.title'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('shares.title')" :subtitle="t('shares.subtitle')">
        <x-slot:actions>
            <x-btn icon="plus" x-data x-on:click="$dispatch('open-modal', 'share')">{{ t('shares.recordPurchase') }}</x-btn>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat :label="t('shares.shareCapital')" :value="money($summary['capital'], true)" />
        <x-stat tone="tertiary" :label="t('shares.sharesOutstanding')" :value="num($summary['quantity'])" />
        <x-stat tone="secondary" :label="t('shares.holders')" :value="$summary['holders']" />
    </div>

    <div class="mt-4">
        @include('partials.toolbar')
        <x-card flush>
            <table class="w-full text-sm">
                <thead><tr class="border-b border-neutral-200 text-left text-[11px] uppercase tracking-wide text-neutral-500">
                    <th class="px-4 py-3">{{ t('common.member') }}</th>
                    <th class="px-4 py-3">{{ t('common.reference') }}</th>
                    <th class="px-4 py-3 text-right">{{ t('shares.quantity') }}</th>
                    <th class="px-4 py-3 text-right">{{ t('shares.pricePerShare') }}</th>
                    <th class="px-4 py-3 text-right">{{ t('shares.totalValue') }}</th>
                    <th class="px-4 py-3">{{ t('shares.purchasedAt') }}</th>
                    <th></th>
                </tr></thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse ($shares as $s)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-4 py-3"><div class="flex items-center gap-2.5"><x-avatar :name="$s->member->full_name" :color="$s->member->avatar_color" size="sm" /><span><span class="block text-[13px] font-medium">{{ $s->member->full_name }}</span><span class="block text-[11px] text-neutral-400">{{ $s->member->member_number }}</span></span></div></td>
                            <td class="px-4 py-3 font-mono text-[12px] text-neutral-500">{{ $s->transaction_reference }}</td>
                            <td class="px-4 py-3 text-right">{{ num($s->quantity) }}</td>
                            <td class="px-4 py-3 text-right">{{ money($s->price_per_share) }}</td>
                            <td class="px-4 py-3 text-right font-medium">{{ money($s->total_value) }}</td>
                            <td class="px-4 py-3 text-[13px] text-neutral-500">{{ fdate($s->purchased_at) }}</td>
                            <td class="px-4 py-3 text-right"><x-row-actions :delete="route('admin.shares.destroy', $s)" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty :title="t('common.noData')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
            @if ($shares->hasPages())<div class="border-t border-neutral-100 px-4 py-3">{{ $shares->links() }}</div>@endif
        </x-card>
    </div>

    <x-modal name="share" :title="t('shares.recordPurchase')">
        <form method="POST" action="{{ route('admin.shares.store') }}" class="grid gap-4">
            @csrf
            <x-field :label="t('common.member')"><x-select name="member_id">@foreach ($members as $m)<option value="{{ $m->id }}">{{ $m->full_name }} — {{ $m->member_number }}</option>@endforeach</x-select></x-field>
            <div class="grid grid-cols-2 gap-4">
                <x-field :label="t('shares.quantity')"><x-input type="number" name="quantity" value="10" /></x-field>
                <x-field :label="t('shares.pricePerShare')"><x-input type="number" name="price_per_share" value="10000" /></x-field>
            </div>
            <x-field :label="t('shares.purchasedAt')"><x-input type="date" name="purchased_at" value="{{ now()->toDateString() }}" /></x-field>
            <button class="k-btn k-btn-primary">{{ t('common.save') }}</button>
        </form>
    </x-modal>
</x-layouts.app>

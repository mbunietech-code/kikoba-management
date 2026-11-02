@php $title = t($i18n.'.title'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t($i18n.'.title')" :subtitle="t($i18n.'.subtitle')">
        <x-slot:actions>
            @can('shares.create')<x-btn icon="plus" x-data x-on:click="$dispatch('open-modal', 'share')">{{ t($i18n.'.recordPurchase') }}</x-btn>@endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat :label="t('shares.shareCapital')" :value="money($summary['capital'])" />
        <x-stat :label="t('shares.sharesOutstanding')" :value="num($summary['quantity'])" />
        <x-stat :label="t('shares.holders')" :value="$summary['holders']" />
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
                            <td class="px-4 py-3"><div class="flex items-center gap-2.5"><x-avatar :name="$s->member?->full_name ?? '—'" :color="$s->member?->avatar_color" size="sm" /><span><span class="block text-[13px] font-medium">{{ $s->member?->full_name ?? '—' }}</span><span class="block text-[11px] text-neutral-400">{{ $s->member?->member_number }}</span></span></div></td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-[12px] text-neutral-500">{{ $s->transaction_reference }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ num($s->quantity) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ money($s->price_per_share) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-medium tabular-nums">{{ money($s->total_value) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-[13px] text-neutral-500">{{ fdate($s->purchased_at) }}</td>
                            <td class="px-4 py-3 text-right">@can('shares.create')<x-row-actions :delete="route($rp.'.destroy', $s)" :confirm="t('shares.deleteConfirm', ['amount' => money($s->total_value), 'name' => $s->member?->full_name])" />@endcan</td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty :title="t('common.noData')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
            @if ($shares->hasPages())<div class="border-t border-neutral-100 px-4 py-3">{{ $shares->links() }}</div>@endif
        </x-card>
    </div>

    <x-modal name="share" :title="t($i18n.'.recordPurchase')">
        <form method="POST" action="{{ route($rp.'.store') }}" class="grid gap-4"
              x-data="{ qty: {{ (int) old('quantity', 1) }}, price: {{ (int) old('price_per_share', $sharePrice ?? 0) }}, busy: false }" x-on:submit="busy = true">
            @csrf
            <x-field :label="t('common.member')" name="member_id">
                <x-select name="member_id" required>
                    <option value="">{{ t('savings.chooseMember') }}</option>
                    @foreach ($members as $m)<option value="{{ $m->id }}" @selected(old('member_id') === $m->id)>{{ $m->full_name }} — {{ $m->member_number }}</option>@endforeach
                </x-select>
            </x-field>
            <div class="grid grid-cols-2 gap-4">
                <x-field :label="t('shares.quantity')" name="quantity"><x-input type="number" name="quantity" min="1" step="1" required x-model.number="qty" /></x-field>
                <x-field :label="t('shares.pricePerShare')" :hint="$sharePrice ? t('shares.fromSettings') : null" name="price_per_share"><x-input type="number" name="price_per_share" min="1" step="1" required x-model.number="price" /></x-field>
            </div>
            <x-field :label="t('shares.purchasedAt')" name="purchased_at"><x-input type="date" name="purchased_at" max="{{ now()->toDateString() }}" value="{{ old('purchased_at', now()->toDateString()) }}" /></x-field>
            <p class="rounded-md bg-neutral-50 px-3 py-2 text-[13px] text-neutral-600">{{ t('shares.totalValue') }}:
                <b class="text-neutral-900" x-text="'{{ app('kikoba.currency') }} ' + ((qty || 0) * (price || 0)).toLocaleString('en-US')"></b></p>
            <div class="flex justify-end gap-2">
                <button type="button" class="k-btn k-btn-outlined" x-on:click="$dispatch('close-modal','share')">{{ t('common.cancel') }}</button>
                <button class="k-btn k-btn-primary" x-bind:disabled="busy">{{ t('common.save') }}</button>
            </div>
        </form>
    </x-modal>
</x-layouts.app>

@php $title = t('nav.myShares'); $total = $shares->sum('total_value'); $qty = $shares->sum('quantity'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('nav.myShares')" :subtitle="t('shares.subtitle')" />
    <div class="grid gap-4 sm:grid-cols-2">
        <x-stat :label="t('shares.sharesHeld')" :value="num($qty)" />
        <x-stat tone="tertiary" :label="t('member.shareValue')" :value="money($total)" />
    </div>
    <x-card class="mt-4" flush>
        <x-mini-table :head="[t('common.reference'), t('shares.quantity'), t('shares.pricePerShare'), t('shares.totalValue'), t('shares.purchasedAt')]"
            :align="['','right','right','right','']"
            :rows="$shares->map(fn ($s) => [$s->transaction_reference, num($s->quantity), money($s->price_per_share), money($s->total_value), fdate($s->purchased_at)])" />
    </x-card>
</x-layouts.app>

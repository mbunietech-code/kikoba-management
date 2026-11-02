@php $editing = $product->exists; $title = $editing ? t('common.edit').' — '.$product->name : t('products.addProduct'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="$title" :subtitle="t('products.subtitle')" :back="route('admin.products.index')" :backLabel="t('products.title')" />
    <form method="POST" action="{{ $editing ? route('admin.products.update',$product) : route('admin.products.store') }}" class="max-w-2xl">
        @csrf @if ($editing) @method('PUT') @endif
        <x-card>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field :label="t('common.name')" name="name" class="sm:col-span-2"><x-input name="name" required maxlength="120" :value="old('name',$product->name)" /></x-field>
                <x-field :label="t('common.description')" name="description" class="sm:col-span-2"><x-input name="description" :value="old('description',$product->description)" /></x-field>
                <x-field :label="t('products.minAmount')" name="minimum_amount"><x-input type="number" min="0" required name="minimum_amount" :value="old('minimum_amount',$product->minimum_amount)" /></x-field>
                <x-field :label="t('products.maxAmount')" name="maximum_amount"><x-input type="number" min="0" required name="maximum_amount" :value="old('maximum_amount',$product->maximum_amount)" /></x-field>
                <x-field :label="t('products.interestRatePerYear')" :hint="t('products.interestRateHint')" name="interest_rate"><x-input type="number" step="0.01" min="0" max="100" required name="interest_rate" :value="old('interest_rate',$product->interest_rate)" /></x-field>
                <x-field :label="t('products.interestMethod')" name="interest_method"><x-select name="interest_method">@foreach (['reducing','flat'] as $m)<option value="{{ $m }}" @selected(old('interest_method',$product->interest_method)===$m)>{{ t("loans.method.$m") }}</option>@endforeach</x-select></x-field>
                <x-field :label="t('products.periodMonths')" :hint="t('products.monthlyOnly')" name="repayment_period"><x-input type="number" min="1" max="120" required name="repayment_period" :value="old('repayment_period',$product->repayment_period ?? 6)" /></x-field>
                <input type="hidden" name="repayment_frequency" value="monthly">
                <x-field :label="t('products.processingFeePct')" :hint="t('products.ofPrincipal')" name="processing_fee"><x-input type="number" step="0.01" min="0" max="100" name="processing_fee" :value="old('processing_fee',$product->processing_fee ?? 0)" /></x-field>
                <x-field :label="t('products.insuranceFeePct')" :hint="t('products.ofPrincipal')" name="insurance_fee"><x-input type="number" step="0.01" min="0" max="100" name="insurance_fee" :value="old('insurance_fee',$product->insurance_fee ?? 0)" /></x-field>
                <x-field :label="t('products.penaltyRate')" :hint="t('products.penaltyNotApplied')" name="penalty_rate"><x-input type="number" step="0.1" min="0" max="100" name="penalty_rate" :value="old('penalty_rate',$product->penalty_rate ?? 0)" /></x-field>
                <x-field :label="t('products.minSavings')" name="minimum_savings"><x-input type="number" min="0" name="minimum_savings" :value="old('minimum_savings',$product->minimum_savings ?? 0)" /></x-field>
                <x-field :label="t('products.minShares')" name="minimum_shares"><x-input type="number" min="0" name="minimum_shares" :value="old('minimum_shares',$product->minimum_shares ?? 0)" /></x-field>
                <x-field :label="t('products.requiredGuarantors')" name="required_guarantors"><x-input type="number" min="0" max="10" name="required_guarantors" :value="old('required_guarantors',$product->required_guarantors ?? 0)" /></x-field>
                <x-field :label="t('common.status')" name="status"><x-select name="status">@foreach (['active','inactive'] as $s)<option value="{{ $s }}" @selected(old('status',$product->status ?? 'active')===$s)>{{ t('products.statuses.'.$s) }}</option>@endforeach</x-select></x-field>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <x-btn variant="outlined" :href="route('admin.products.index')">{{ t('common.cancel') }}</x-btn>
                <button class="k-btn k-btn-primary">{{ t('common.save') }}</button>
            </div>
        </x-card>
    </form>
</x-layouts.app>

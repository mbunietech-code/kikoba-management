@php $editing = $product->exists; $title = $editing ? t('common.edit').' — '.$product->name : t('products.addProduct'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="$title" :subtitle="t('products.subtitle')" :back="route('admin.products.index')" :backLabel="t('products.title')" />
    <form method="POST" action="{{ $editing ? route('admin.products.update',$product) : route('admin.products.store') }}" class="max-w-2xl">
        @csrf @if ($editing) @method('PUT') @endif
        <x-card>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field :label="t('common.name')" name="name" class="sm:col-span-2"><x-input name="name" :value="old('name',$product->name)" /></x-field>
                <x-field :label="t('common.description')" name="description" class="sm:col-span-2"><x-input name="description" :value="old('description',$product->description)" /></x-field>
                <x-field :label="t('products.minAmount')" name="minimum_amount"><x-input type="number" name="minimum_amount" :value="old('minimum_amount',$product->minimum_amount ?? 100000)" /></x-field>
                <x-field :label="t('products.maxAmount')" name="maximum_amount"><x-input type="number" name="maximum_amount" :value="old('maximum_amount',$product->maximum_amount ?? 5000000)" /></x-field>
                <x-field :label="t('products.interestRate')" name="interest_rate"><x-input type="number" step="0.1" name="interest_rate" :value="old('interest_rate',$product->interest_rate ?? 10)" /></x-field>
                <x-field :label="t('products.interestMethod')" name="interest_method"><x-select name="interest_method">@foreach (['reducing','flat'] as $m)<option value="{{ $m }}" @selected(old('interest_method',$product->interest_method)===$m)>{{ t("loans.method.$m") }}</option>@endforeach</x-select></x-field>
                <x-field :label="t('loans.period')" name="repayment_period"><x-input type="number" name="repayment_period" :value="old('repayment_period',$product->repayment_period ?? 6)" /></x-field>
                <x-field :label="t('loans.frequency')" name="repayment_frequency"><x-select name="repayment_frequency">@foreach (['weekly','biweekly','monthly','quarterly'] as $f)<option value="{{ $f }}" @selected(old('repayment_frequency',$product->repayment_frequency ?? 'monthly')===$f)>{{ t("loans.freq.$f") }}</option>@endforeach</x-select></x-field>
                <x-field :label="t('products.processingFee')" name="processing_fee"><x-input type="number" step="0.1" name="processing_fee" :value="old('processing_fee',$product->processing_fee ?? 1)" /></x-field>
                <x-field :label="t('products.penaltyRate')" name="penalty_rate"><x-input type="number" step="0.1" name="penalty_rate" :value="old('penalty_rate',$product->penalty_rate ?? 5)" /></x-field>
                <x-field :label="t('products.minSavings')" name="minimum_savings"><x-input type="number" name="minimum_savings" :value="old('minimum_savings',$product->minimum_savings ?? 100000)" /></x-field>
                <x-field :label="t('products.minShares')" name="minimum_shares"><x-input type="number" name="minimum_shares" :value="old('minimum_shares',$product->minimum_shares ?? 10)" /></x-field>
                <x-field :label="t('products.requiredGuarantors')" name="required_guarantors"><x-input type="number" name="required_guarantors" :value="old('required_guarantors',$product->required_guarantors ?? 2)" /></x-field>
                <x-field :label="t('common.status')" name="status"><x-select name="status">@foreach (['active','inactive'] as $s)<option value="{{ $s }}" @selected(old('status',$product->status ?? 'active')===$s)>{{ ucfirst($s) }}</option>@endforeach</x-select></x-field>
                <input type="hidden" name="insurance_fee" value="{{ $product->insurance_fee ?? 1 }}">
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <x-btn variant="outlined" :href="route('admin.products.index')">{{ t('common.cancel') }}</x-btn>
                <button class="k-btn k-btn-primary">{{ t('common.save') }}</button>
            </div>
        </x-card>
    </form>
</x-layouts.app>

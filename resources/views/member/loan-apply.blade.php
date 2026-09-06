@php $title = t('member.applyLoan'); $p0 = $products->first(); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('member.applyLoan')" :subtitle="t('loans.newApplication')" :back="route('member.loans')" :backLabel="t('nav.myLoans')" />

    <form method="POST" action="{{ route('member.loans.apply.store') }}"
          x-data="{
            products: @js($products->mapWithKeys(fn($p) => [$p->id => ['rate' => (float) $p->interest_rate, 'fee' => (float) $p->processing_fee, 'ins' => (float) $p->insurance_fee, 'min' => $p->minimum_amount, 'max' => $p->maximum_amount, 'method' => $p->interest_method]])),
            pid: '{{ $p0?->id }}', amount: 1000000, period: 6,
            get p() { return this.products[this.pid] || {} },
            get interest() { return Math.round(this.amount * (this.p.rate||0) * this.period / 1200) },
            get fees() { return Math.round(this.amount * ((this.p.fee||0) + (this.p.ins||0)) / 100) },
            get total() { return Number(this.amount) + this.interest + this.fees },
            get installment() { return Math.round(this.total / this.period) },
            fmt(v) { return 'TZS ' + Number(v).toLocaleString() }
          }"
          class="grid gap-4 lg:grid-cols-[1fr_360px]">
        @csrf
        <div class="flex flex-col gap-4">
            <x-card>
                <x-card-header :title="t('loans.newApplication')" />
                <div class="mt-2 grid gap-4">
                    <x-field :label="t('loans.product')">
                        <x-select name="loan_product_id" x-model="pid">
                            @foreach ($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                        </x-select>
                    </x-field>
                    <x-field :label="t('loans.principal')">
                        <input type="range" name="amount" x-model.number="amount" :min="p.min" :max="p.max" step="50000" class="w-full">
                        <p class="mt-1 text-[13px] font-semibold" x-text="fmt(amount)"></p>
                    </x-field>
                    <x-field :label="t('loans.period')">
                        <input type="range" name="period" x-model.number="period" min="1" max="24" step="1" class="w-full">
                        <p class="mt-1 text-[13px] font-semibold"><span x-text="period"></span> {{ t('loans.months') }}</p>
                    </x-field>
                    <x-field :label="t('loans.purpose')"><x-input name="purpose" placeholder="e.g. Restock shop inventory" /></x-field>
                </div>
            </x-card>
        </div>

        <div class="flex flex-col gap-4">
            <x-card>
                <x-card-header :title="t('common.summary')" />
                <div class="mt-2 space-y-2 text-[13px]">
                    <div class="flex justify-between"><span class="text-neutral-500">{{ t('loans.principal') }}</span><span class="font-medium" x-text="fmt(amount)"></span></div>
                    <div class="flex justify-between"><span class="text-neutral-500">{{ t('loans.interest') }}</span><span class="font-medium" x-text="fmt(interest)"></span></div>
                    <div class="flex justify-between"><span class="text-neutral-500">{{ t('loans.fees') }}</span><span class="font-medium" x-text="fmt(fees)"></span></div>
                    <div class="flex justify-between border-t border-neutral-100 pt-2"><span class="font-semibold">{{ t('loans.totalRepayable') }}</span><span class="font-bold" x-text="fmt(total)"></span></div>
                    <div class="flex justify-between"><span class="text-neutral-500">{{ t('loans.installment') }}</span><span class="font-medium" x-text="fmt(installment)"></span></div>
                </div>
            </x-card>
            <button class="k-btn k-btn-primary h-12">{{ t('common.submit') }}</button>
        </div>
    </form>
</x-layouts.app>

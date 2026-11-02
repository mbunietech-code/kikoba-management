@php $title = t('member.applyLoan'); $p0 = $products->first(); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('member.applyLoan')" :subtitle="t('member.applyIntro')" :back="route('member.loans')" :backLabel="t('nav.myLoans')" />

    @if ($products->isEmpty())
        <x-card><x-empty :title="t('member.noProducts')" :hint="t('member.noProductsHint')" icon="credit-card" /></x-card>
    @else
    <form method="POST" action="{{ route('member.loans.apply.store') }}"
          x-data="{
            products: @js($products->mapWithKeys(fn($p) => [$p->id => ['rate' => (float) $p->interest_rate, 'fee' => (float) $p->processing_fee, 'ins' => (float) $p->insurance_fee, 'min' => (int) $p->minimum_amount, 'max' => (int) $p->maximum_amount, 'months' => (int) $p->repayment_period, 'method' => $p->interest_method]])),
            pid: @js(old('loan_product_id', $p0->id)),
            amount: {{ (int) old('amount', $p0->minimum_amount) }},
            period: {{ (int) old('period', min(6, $p0->repayment_period)) }},
            busy: false,
            get p() { return this.products[this.pid] || {} },
            get interest() {
                const a = Number(this.amount) || 0, n = Number(this.period) || 1, r = this.p.rate || 0;
                if (this.p.method === 'flat') return Math.round(a * r * n / 1200);
                let balance = a, total = 0;
                for (let k = 0; k < n; k++) { total += balance * r / 1200; balance -= a / n; }
                return Math.round(total);
            },
            get fees() { return Math.round((Number(this.amount) || 0) * ((this.p.fee || 0) + (this.p.ins || 0)) / 100) },
            get total() { return (Number(this.amount) || 0) + this.interest + this.fees },
            get installment() { return Math.round(this.total / (Number(this.period) || 1)) },
            fmt(v) { return '{{ app('kikoba.currency') }} ' + Number(v).toLocaleString('en-US') }
          }"
          x-on:submit="busy = true"
          class="grid gap-4 lg:grid-cols-[1fr_340px]">
        @csrf
        <x-card>
            <div class="grid gap-4">
                <x-field :label="t('loans.product')" name="loan_product_id">
                    <x-select name="loan_product_id" x-model="pid" x-on:change="amount = Math.max(p.min, Math.min(amount, p.max)); period = Math.min(period, p.months)">
                        @foreach ($products as $p)<option value="{{ $p->id }}">{{ $p->name }} — {{ pct($p->interest_rate) }} {{ t('member.perYear') }}</option>@endforeach
                    </x-select>
                </x-field>
                <x-field :label="t('member.amountToBorrow').' ('.app('kikoba.currency').')'" name="amount">
                    <x-input type="number" name="amount" step="1000" required inputmode="numeric" x-model.number="amount" x-bind:min="p.min" x-bind:max="p.max" />
                    <p class="mt-1 text-[12px] text-neutral-500" x-text="'{{ t('member.allowedRange') }} ' + fmt(p.min) + ' – ' + fmt(p.max)"></p>
                </x-field>
                <x-field :label="t('member.monthsToRepay')" name="period">
                    <x-input type="number" name="period" min="1" step="1" required x-model.number="period" x-bind:max="p.months" />
                    <p class="mt-1 text-[12px] text-neutral-500" x-text="'{{ t('member.upToMonths') }} ' + p.months"></p>
                </x-field>
                <x-field :label="t('loans.purpose')" name="purpose">
                    <x-input name="purpose" required maxlength="255" :value="old('purpose')" :placeholder="t('member.purposeExample')" />
                </x-field>
            </div>
        </x-card>

        <div class="flex flex-col gap-4">
            <x-card>
                <x-card-header :title="t('member.whatYouWillRepay')" :subtitle="t('member.estimateNote')" />
                <dl class="space-y-2 text-[13.5px]">
                    <div class="flex justify-between"><dt class="text-neutral-500">{{ t('loans.principal') }}</dt><dd class="font-medium tabular-nums" x-text="fmt(amount)"></dd></div>
                    <div class="flex justify-between"><dt class="text-neutral-500">{{ t('loans.interest') }}</dt><dd class="font-medium tabular-nums" x-text="fmt(interest)"></dd></div>
                    <div class="flex justify-between"><dt class="text-neutral-500">{{ t('loans.fees') }}</dt><dd class="font-medium tabular-nums" x-text="fmt(fees)"></dd></div>
                    <div class="flex justify-between border-t border-neutral-100 pt-2"><dt class="font-semibold">{{ t('loans.totalRepayable') }}</dt><dd class="font-bold tabular-nums" x-text="fmt(total)"></dd></div>
                    <div class="flex justify-between"><dt class="text-neutral-500">{{ t('member.monthlyPayment') }}</dt><dd class="font-semibold tabular-nums" x-text="fmt(installment)"></dd></div>
                </dl>
            </x-card>
            <button class="k-btn k-btn-primary h-11" x-bind:disabled="busy">{{ t('member.sendApplication') }}</button>
            <p class="text-[12.5px] text-neutral-500">{{ t('member.afterApplying') }}</p>
        </div>
    </form>
    @endif
</x-layouts.app>

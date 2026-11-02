@php $title = t('profit.newDistribution'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="$title" :subtitle="t('profit.createIntro')" :back="route('admin.profit.index')" :backLabel="t('profit.title')" />

    <div class="grid max-w-3xl gap-4">
        <x-card>
            <x-card-header :title="t('profit.step1')" :subtitle="t('profit.step1Hint')" />
            <form method="GET" action="{{ route('admin.profit.create') }}" class="flex flex-wrap items-end gap-3">
                <x-field :label="t('profit.periodStart')" class="min-w-[160px] flex-1"><x-input type="date" name="period_start" :value="$start->toDateString()" required /></x-field>
                <x-field :label="t('profit.periodEnd')" class="min-w-[160px] flex-1"><x-input type="date" name="period_end" :value="$end->toDateString()" max="{{ now()->toDateString() }}" required /></x-field>
                <button class="k-btn k-btn-outlined">{{ t('profit.checkBooks') }}</button>
            </form>
            <p class="mt-4 rounded-md bg-neutral-50 px-3 py-2.5 text-[13.5px] text-neutral-700">
                {{ t('profit.booksSay', ['start' => fdate($start), 'end' => fdate($end)]) }}
                <b class="{{ $booksProfit < 0 ? 'text-red-700' : 'text-neutral-900' }}">{{ money($booksProfit) }}</b>
            </p>
        </x-card>

        <x-card>
            <x-card-header :title="t('profit.step2')" />
            <form method="POST" action="{{ route('admin.profit.store') }}" class="grid gap-4"
                  x-data="{ total: {{ (int) old('total_profit', max(0, $booksProfit)) }}, reserve: {{ (float) old('reserve_percent', 0) }}, busy: false }" x-on:submit="busy = true">
                @csrf
                <input type="hidden" name="period_start" value="{{ $start->toDateString() }}">
                <input type="hidden" name="period_end" value="{{ $end->toDateString() }}">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field :label="t('profit.totalProfit').' ('.app('kikoba.currency').')'" :hint="t('profit.fromBooksEditable')" name="total_profit">
                        <x-input type="number" name="total_profit" min="1" step="1" required x-model.number="total" />
                    </x-field>
                    <x-field :label="t('profit.reservePercent')" :hint="t('profit.reserveHint')" name="reserve_percent">
                        <x-input type="number" name="reserve_percent" min="0" max="100" step="0.5" required x-model.number="reserve" />
                    </x-field>
                </div>
                <fieldset>
                    <legend class="k-label mb-2">{{ t('profit.basis') }}</legend>
                    <div class="grid gap-2 sm:grid-cols-3">
                        @foreach (['shares' => 'basisSharesHint', 'savings' => 'basisSavingsHint', 'equal' => 'basisEqualHint'] as $basis => $hint)
                            <label class="flex cursor-pointer gap-2.5 rounded-md border border-neutral-200 p-3 has-[:checked]:border-primary-600 has-[:checked]:bg-primary-50/50">
                                <input type="radio" name="basis" value="{{ $basis }}" class="mt-0.5 accent-[var(--color-primary-800)]" @checked(old('basis', 'shares') === $basis)>
                                <span><span class="block text-[13.5px] font-medium text-neutral-900">{{ t('profit.basis'.ucfirst($basis)) }}</span>
                                    <span class="block text-[12px] text-neutral-500">{{ t('profit.'.$hint) }}</span></span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <dl class="grid gap-1 rounded-md bg-neutral-50 px-3 py-2.5 text-[13.5px]">
                    <div class="flex justify-between"><dt class="text-neutral-500">{{ t('profit.reservedAmount') }}</dt><dd class="tabular-nums" x-text="'{{ app('kikoba.currency') }} ' + Math.round((total || 0) * (reserve || 0) / 100).toLocaleString('en-US')"></dd></div>
                    <div class="flex justify-between font-semibold"><dt>{{ t('profit.toShare') }}</dt><dd class="tabular-nums" x-text="'{{ app('kikoba.currency') }} ' + ((total || 0) - Math.round((total || 0) * (reserve || 0) / 100)).toLocaleString('en-US')"></dd></div>
                </dl>
                <div class="flex justify-end gap-2">
                    <x-btn variant="outlined" :href="route('admin.profit.index')">{{ t('common.cancel') }}</x-btn>
                    <button class="k-btn k-btn-primary" x-bind:disabled="busy">{{ t('profit.calculate') }}</button>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.app>

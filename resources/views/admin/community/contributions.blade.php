@php
    $title = t('community.title');
    $paidCount = $members->filter(fn ($m) => ($paid[$m->id]['jamii'] ?? 0) >= $rates['jamii'] && ($paid[$m->id]['dharura'] ?? 0) >= $rates['dharura'])->count();
    $prev = \Illuminate\Support\Carbon::createFromFormat('!Y-m', $period)->subMonth()->format('Y-m');
    $next = \Illuminate\Support\Carbon::createFromFormat('!Y-m', $period)->addMonth()->format('Y-m');
@endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('community.title')" :subtitle="t('community.contributionsSubtitle')">
        <x-slot:actions>
            @can('savings.deposit')<x-btn icon="plus" x-data x-on:click="$dispatch('open-modal','contribute')">{{ t('community.recordContribution') }}</x-btn>@endcan
        </x-slot:actions>
    </x-page-header>
    @include('admin.community._tabs', ['current' => 'contributions'])

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat :label="t('community.jamiiFund')" :value="money($funds['jamii'])" :hint="t('community.fundHint')" />
        <x-stat :label="t('community.dharuraFund')" :value="money($funds['dharura'])" :hint="t('community.fundHint')" />
        <x-stat :label="t('community.collectedIn', ['month' => $periodLabel])" :value="money($monthTotals['jamii'] + $monthTotals['dharura'])"
                :hint="t('community.jamii').' '.num($monthTotals['jamii']).' · '.t('community.dharura').' '.num($monthTotals['dharura'])" />
        <x-card>
            <div class="flex items-start justify-between gap-2">
                <p class="text-[12.5px] font-medium text-neutral-500">{{ t('community.standardAmounts') }}</p>
                @can('settings.manage')<button type="button" x-data x-on:click="$dispatch('open-modal','rates')" class="text-[12px] font-medium text-primary-700 hover:underline">{{ t('common.edit') }}</button>@endcan
            </div>
            <dl class="mt-2 space-y-1 text-[13.5px]">
                <div class="flex justify-between"><dt class="text-neutral-600">{{ t('community.jamii') }}</dt><dd class="font-semibold tabular-nums">{{ money($rates['jamii']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-neutral-600">{{ t('community.dharura') }}</dt><dd class="font-semibold tabular-nums">{{ money($rates['dharura']) }}</dd></div>
            </dl>
        </x-card>
    </div>

    <x-card class="mt-4" flush>
        <div class="flex flex-wrap items-center justify-between gap-3 px-4 pt-4">
            <div>
                <p class="text-[15px] font-bold text-neutral-900">{{ t('community.monthStatus', ['month' => $periodLabel]) }}</p>
                <p class="text-[12.5px] text-neutral-500">{{ t('community.paidInFull', ['paid' => $paidCount, 'total' => $members->count()]) }}</p>
            </div>
            <form method="GET" action="{{ route('admin.community.index') }}" class="flex items-center gap-1">
                <a href="{{ route('admin.community.index', ['month' => $prev]) }}" class="k-btn k-btn-outlined k-btn-sm" aria-label="{{ t('community.previousMonth') }}"><x-heroicon-o-chevron-left class="h-4 w-4" /></a>
                <input type="month" name="month" value="{{ $period }}" class="k-input h-8 w-40 text-[13px]" onchange="this.form.submit()">
                <a href="{{ route('admin.community.index', ['month' => $next]) }}" class="k-btn k-btn-outlined k-btn-sm" aria-label="{{ t('community.nextMonth') }}"><x-heroicon-o-chevron-right class="h-4 w-4" /></a>
            </form>
        </div>
        <table class="mt-3 w-full text-sm">
            <thead><tr class="border-y border-neutral-200 text-left text-[11px] uppercase tracking-wide text-neutral-500">
                <th class="px-4 py-3">{{ t('common.member') }}</th>
                <th class="px-4 py-3 text-right">{{ t('community.jamii') }}</th>
                <th class="px-4 py-3 text-right">{{ t('community.dharura') }}</th>
                <th class="px-4 py-3">{{ t('common.status') }}</th>
                <th class="px-4 py-3"></th>
            </tr></thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse ($members as $m)
                    @php
                        $j = $paid[$m->id]['jamii'] ?? 0;
                        $d = $paid[$m->id]['dharura'] ?? 0;
                        $state = ($j >= $rates['jamii'] && $d >= $rates['dharura']) ? 'paid' : (($j + $d) > 0 ? 'partial' : 'pending');
                    @endphp
                    <tr class="hover:bg-neutral-50">
                        <td class="px-4 py-3"><span class="text-[13.5px] font-medium text-neutral-800">{{ $m->full_name }}</span> <span class="text-[12px] text-neutral-400">{{ $m->member_number }}</span></td>
                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums {{ $j ? 'text-neutral-900' : 'text-neutral-400' }}">{{ money($j, symbol: false) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums {{ $d ? 'text-neutral-900' : 'text-neutral-400' }}">{{ money($d, symbol: false) }}</td>
                        <td class="px-4 py-3"><x-status :status="$state" :label="t('community.state.'.$state)" /></td>
                        <td class="px-4 py-3 text-right">
                            @if ($state !== 'paid')
                                @can('savings.deposit')
                                    <button type="button" class="text-[12.5px] font-medium text-primary-700 hover:underline"
                                            x-data x-on:click="$dispatch('contribute-for', { member: @js($m->id), jamii: {{ max(0, $rates['jamii'] - $j) }}, dharura: {{ max(0, $rates['dharura'] - $d) }} })">{{ t('community.record') }}</button>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty :title="t('community.noActiveMembers')" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </x-card>

    <x-card class="mt-4" flush>
        <p class="px-4 pt-4 text-[15px] font-bold text-neutral-900">{{ t('community.recentContributions') }}</p>
        <table class="mt-3 w-full text-sm">
            <thead><tr class="border-y border-neutral-200 text-left text-[11px] uppercase tracking-wide text-neutral-500">
                <th class="px-4 py-3">{{ t('common.date') }}</th>
                <th class="px-4 py-3">{{ t('common.member') }}</th>
                <th class="px-4 py-3">{{ t('community.fund') }}</th>
                <th class="px-4 py-3">{{ t('community.forMonth') }}</th>
                <th class="px-4 py-3 text-right">{{ t('common.amount') }}</th>
                <th class="px-4 py-3">{{ t('common.reference') }}</th>
                <th class="px-4 py-3"></th>
            </tr></thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse ($recent as $c)
                    <tr class="{{ $c->status === 'reversed' ? 'text-neutral-400' : '' }}">
                        <td class="whitespace-nowrap px-4 py-3 text-[13px]">{{ fdate($c->paid_on) }}</td>
                        <td class="px-4 py-3 text-[13px]">{{ $c->member?->full_name }}</td>
                        <td class="px-4 py-3 text-[13px]">{{ t('community.'.$c->fund) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-[13px]">{{ \Illuminate\Support\Carbon::createFromFormat('!Y-m', $c->period)->translatedFormat('M Y') }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right font-medium tabular-nums {{ $c->status === 'reversed' ? 'line-through' : '' }}">{{ money($c->amount) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 font-mono text-[12px] text-neutral-500">{{ $c->reference }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($c->status === 'reversed')
                                <x-badge tone="neutral" :title="$c->reversal_reason">{{ t('transactions.statuses.reversed') }}</x-badge>
                            @else
                                @can('savings.withdraw')
                                    <x-reverse-action :action="route('admin.community.contributions.reverse', $c)" :summary="t('community.'.$c->fund).' · '.$c->member?->full_name.' · '.money($c->amount)" />
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty :title="t('community.noContributions')" icon="hand-raised" /></td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($recent->hasPages())<div class="border-t border-neutral-100 px-4 py-3">{{ $recent->links() }}</div>@endif
    </x-card>

    @can('savings.deposit')
        <x-modal name="contribute" :title="t('community.recordContribution')">
            <form method="POST" action="{{ route('admin.community.contributions.store') }}" class="grid gap-4"
                  x-data="{ member: @js(old('member_id', '')), jamii: {{ (int) old('jamii_amount', $rates['jamii']) }}, dharura: {{ (int) old('dharura_amount', $rates['dharura']) }}, busy: false }"
                  x-on:contribute-for.window="member = $event.detail.member; jamii = $event.detail.jamii; dharura = $event.detail.dharura; $dispatch('open-modal', 'contribute')"
                  x-on:submit="busy = true">
                @csrf
                <x-field :label="t('common.member')" name="member_id">
                    <x-select name="member_id" required x-model="member">
                        <option value="">{{ t('savings.chooseMember') }}</option>
                        @foreach ($members as $m)<option value="{{ $m->id }}">{{ $m->full_name }} — {{ $m->member_number }}</option>@endforeach
                    </x-select>
                </x-field>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field :label="t('community.jamii').' ('.app('kikoba.currency').')'" :hint="t('community.standard').' '.num($rates['jamii'])" name="jamii_amount">
                        <x-input type="number" name="jamii_amount" min="0" step="500" required x-model.number="jamii" />
                    </x-field>
                    <x-field :label="t('community.dharura').' ('.app('kikoba.currency').')'" :hint="t('community.standard').' '.num($rates['dharura'])" name="dharura_amount">
                        <x-input type="number" name="dharura_amount" min="0" step="500" required x-model.number="dharura" />
                    </x-field>
                    <x-field :label="t('community.forMonth')" name="period"><x-input type="month" name="period" required :value="old('period', $period)" /></x-field>
                    <x-field :label="t('community.paidOn')" name="paid_on"><x-input type="date" name="paid_on" required max="{{ now()->toDateString() }}" :value="old('paid_on', now()->toDateString())" /></x-field>
                </div>
                <x-field :label="t('common.reference')" :hint="t('savings.referenceHint')" name="reference"><x-input name="reference" maxlength="100" :value="old('reference')" /></x-field>
                <p class="rounded-md bg-neutral-50 px-3 py-2 text-[13px] text-neutral-600">{{ t('common.total') }}:
                    <b class="text-neutral-900" x-text="'{{ app('kikoba.currency') }} ' + ((jamii || 0) + (dharura || 0)).toLocaleString('en-US')"></b>
                    <span class="text-neutral-500">· {{ t('community.zeroSkips') }}</span></p>
                <div class="flex justify-end gap-2">
                    <button type="button" class="k-btn k-btn-outlined" x-on:click="$dispatch('close-modal','contribute')">{{ t('common.cancel') }}</button>
                    <button class="k-btn k-btn-primary" x-bind:disabled="busy || ((jamii || 0) + (dharura || 0)) <= 0">{{ t('community.save') }}</button>
                </div>
            </form>
        </x-modal>
    @endcan

    @can('settings.manage')
        <x-modal name="rates" :title="t('community.standardAmounts')" size="sm">
            <form method="POST" action="{{ route('admin.community.rates') }}" class="grid gap-4">
                @csrf @method('PUT')
                <p class="text-[13px] text-neutral-600">{{ t('community.ratesHint') }}</p>
                <x-field :label="t('community.jamii').' ('.app('kikoba.currency').')'" name="jamii"><x-input type="number" name="jamii" min="1" step="500" required :value="$rates['jamii']" /></x-field>
                <x-field :label="t('community.dharura').' ('.app('kikoba.currency').')'" name="dharura"><x-input type="number" name="dharura" min="1" step="500" required :value="$rates['dharura']" /></x-field>
                <div class="flex justify-end gap-2">
                    <button type="button" class="k-btn k-btn-outlined" x-on:click="$dispatch('close-modal','rates')">{{ t('common.cancel') }}</button>
                    <button class="k-btn k-btn-primary">{{ t('common.saveChanges') }}</button>
                </div>
            </form>
        </x-modal>
    @endcan
</x-layouts.app>

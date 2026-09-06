@php $title = $member->full_name; $shareValue = $member->shares->sum('total_value'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="$member->full_name" :subtitle="$member->member_number" :back="route('admin.members.index')" :backLabel="t('members.title')">
        <x-slot:actions>
            <x-btn variant="outlined" :href="route('admin.members.edit', $member)" icon="pencil-square">{{ t('common.edit') }}</x-btn>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 lg:grid-cols-[300px_1fr]">
        <x-card>
            <div class="flex flex-col items-center text-center">
                <x-avatar :name="$member->full_name" :color="$member->avatar_color" size="lg" />
                <p class="mt-3 font-display text-lg font-bold text-neutral-900">{{ $member->full_name }}</p>
                <x-status :status="$member->status" :label="t('members.status.'.$member->status)" />
                <div class="mt-4 w-full space-y-2 text-left text-[13px] text-neutral-600">
                    <p class="flex items-center gap-2"><x-heroicon-o-phone class="h-4 w-4 text-neutral-400" /> {{ $member->phone }}</p>
                    <p class="flex items-center gap-2"><x-heroicon-o-envelope class="h-4 w-4 text-neutral-400" /> {{ $member->email ?: '—' }}</p>
                    <p class="flex items-center gap-2"><x-heroicon-o-map-pin class="h-4 w-4 text-neutral-400" /> {{ $member->address ?: '—' }}</p>
                </div>
            </div>
        </x-card>

        <div class="min-w-0">
            <div class="grid gap-3 sm:grid-cols-3">
                <x-stat :label="t('member.shareValue')" :value="money($shareValue, true)" />
                <x-stat tone="secondary" :label="t('member.savingsBalance')" :value="money($member->savingsAccount?->balance ?? 0, true)" />
                <x-stat tone="neutral" :label="t('member.outstanding')" :value="money($member->loans->sum('outstanding_balance'), true)" />
            </div>

            <x-card class="mt-4" flush>
                <x-tabs :current="$tab" :items="[
                    ['key' => 'profile', 'label' => t('members.tabs.profile')],
                    ['key' => 'shares', 'label' => t('members.tabs.shares'), 'count' => $member->shares->count()],
                    ['key' => 'savings', 'label' => t('members.tabs.savings'), 'count' => $member->savingsTransactions->count()],
                    ['key' => 'loans', 'label' => t('members.tabs.loans'), 'count' => $member->loans->count()],
                    ['key' => 'insurance', 'label' => t('members.tabs.insurance')],
                    ['key' => 'transactions', 'label' => t('members.tabs.transactions'), 'count' => $member->transactions->count()],
                ]" />

                <div class="p-4">
                    @if ($tab === 'profile')
                        <x-kv :items="[
                            ['label' => t('members.memberNumber'), 'value' => $member->member_number],
                            ['label' => t('members.gender'), 'value' => $member->gender ? t('members.'.$member->gender) : '—'],
                            ['label' => t('members.dob'), 'value' => fdate($member->date_of_birth)],
                            ['label' => t('members.registrationDate'), 'value' => fdate($member->registration_date)],
                            ['label' => t('members.address'), 'value' => e($member->address ?: '—')],
                            ['label' => t('members.nextOfKin'), 'value' => e(($member->next_of_kin ?: '—').' · '.$member->next_of_kin_phone)],
                            ['label' => t('savings.accountNumber'), 'value' => $member->savingsAccount?->account_number ?? '—'],
                            ['label' => t('common.email'), 'value' => e($member->email ?: '—')],
                        ]" />
                    @elseif ($tab === 'shares')
                        <x-mini-table :head="[t('common.reference'), t('shares.quantity'), t('shares.pricePerShare'), t('shares.totalValue'), t('shares.purchasedAt')]"
                            :rows="$member->shares->map(fn($s) => [$s->transaction_reference, num($s->quantity), money($s->price_per_share), money($s->total_value), fdate($s->purchased_at)])" />
                    @elseif ($tab === 'savings')
                        <x-mini-table :head="[t('common.date'), t('common.type'), t('common.amount'), t('savings.balanceAfter')]"
                            :rows="$member->savingsTransactions->map(fn($s) => [fdate($s->created_at), t('savings.txnType.'.$s->type), ($s->type==='deposit'?'+':'−').money($s->amount, symbol:false), money($s->balance_after)])" />
                    @elseif ($tab === 'loans')
                        <x-mini-table :head="[t('loans.loanNumber'), t('loans.product'), t('loans.principal'), t('loans.outstanding'), t('common.status')]"
                            :rows="$member->loans->map(fn($l) => ['<a class=\"text-primary-700 font-medium\" href=\"'.route('admin.loans.show',$l).'\">'.$l->loan_number.'</a>', e($l->product->name), money($l->principal_amount), money($l->outstanding_balance), '<span class=\"capitalize\">'.str_replace('_',' ',$l->status).'</span>'])"
                            html />
                    @elseif ($tab === 'insurance')
                        @if ($member->insuranceAccount)
                            <x-kv :items="[
                                ['label' => t('insurance.planName'), 'value' => e($member->insuranceAccount->plan_name)],
                                ['label' => t('insurance.monthlyContribution'), 'value' => money($member->insuranceAccount->monthly_contribution)],
                                ['label' => t('insurance.coverageAmount'), 'value' => money($member->insuranceAccount->coverage_amount)],
                                ['label' => t('insurance.endDate'), 'value' => fdate($member->insuranceAccount->end_date)],
                                ['label' => t('common.status'), 'value' => ucfirst($member->insuranceAccount->status)],
                            ]" />
                        @else
                            <p class="text-sm text-neutral-400">{{ t('common.noData') }}</p>
                        @endif
                    @elseif ($tab === 'transactions')
                        <x-mini-table :head="[t('transactions.txnRef'), t('common.type'), t('common.amount'), t('common.date'), t('common.status')]"
                            :rows="$member->transactions->map(fn($r) => [$r->transaction_reference, t('transactions.types.'.strtolower($r->type)), money($r->amount), fdate($r->created_at), ucfirst($r->status)])" />
                    @endif
                </div>
            </x-card>
        </div>
    </div>
</x-layouts.app>

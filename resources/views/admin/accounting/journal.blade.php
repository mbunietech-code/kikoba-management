@php
    $title = $entry->reference;
    $td = $entry->lines->sum('debit'); $tc = $entry->lines->sum('credit');
    $balanced = $td === $tc;
@endphp
<x-layouts.app :title="$title">
    <x-page-header :title="$entry->reference" :subtitle="$entry->description" :back="route('admin.accounting.index', ['tab' => 'journal'])" :backLabel="t('accounting.title')">
        <x-slot:actions>
            <form method="POST" action="{{ route('admin.accounting.journal.reverse', $entry) }}">@csrf<button class="k-btn k-btn-outlined">{{ t('common.reverse') }}</button></form>
        </x-slot:actions>
    </x-page-header>
    <div class="grid gap-4 lg:grid-cols-[1fr_320px]">
        <x-card flush>
            <div class="flex items-center justify-between px-4 pt-4">
                <p class="font-bold text-neutral-900">{{ t('accounting.journalEntries') }}</p>
                <x-badge :tone="$balanced ? 'success' : 'danger'" dot>{{ $balanced ? t('accounting.balanced') : t('accounting.unbalanced') }}</x-badge>
            </div>
            <table class="mt-2 w-full text-sm">
                <thead><tr class="border-b border-neutral-200 text-left text-[11px] uppercase tracking-wide text-neutral-500">
                    <th class="px-4 py-3">{{ t('accounting.accountName') }}</th><th class="px-4 py-3 text-right">{{ t('accounting.debit') }}</th><th class="px-4 py-3 text-right">{{ t('accounting.credit') }}</th></tr></thead>
                <tbody>
                    @foreach ($entry->lines as $l)
                        <tr class="border-b border-neutral-100">
                            <td class="px-4 py-3"><span class="font-mono text-[12px] text-neutral-500">{{ $l->account->account_code }}</span> <span class="font-medium">{{ $l->account->name }}</span></td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $l->debit ? money($l->debit) : '—' }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $l->credit ? money($l->credit) : '—' }}</td>
                        </tr>
                    @endforeach
                    <tr class="font-semibold"><td class="px-4 py-3">{{ t('common.total') }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ money($td) }}</td><td class="px-4 py-3 text-right tabular-nums">{{ money($tc) }}</td></tr>
                </tbody>
            </table>
        </x-card>
        <x-card>
            <x-card-header :title="t('common.details')" />
            <x-kv :cols="1" :items="[
                ['label' => t('common.reference'), 'value' => $entry->reference],
                ['label' => t('accounting.entryDate'), 'value' => fdate($entry->entry_date)],
                ['label' => t('accounting.postedBy'), 'value' => e($entry->posted_by)],
            ]" />
        </x-card>
    </div>
</x-layouts.app>

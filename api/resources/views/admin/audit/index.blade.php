@php $title = t('audit.title'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('audit.title')" :subtitle="t('audit.subtitle')" />
    @include('partials.toolbar', ['filters' => [['name' => 'action', 'value' => request('action'), 'options' => ['' => t('common.all')] + $actions->mapWithKeys(fn ($a) => [$a => str_replace('_', ' ', $a)])->all()]]])
    <x-card flush>
        <table class="w-full text-sm">
            <thead><tr class="border-b border-neutral-200 text-left text-[11px] uppercase tracking-wide text-neutral-500">
                <th class="px-4 py-3">{{ t('audit.timestamp') }}</th><th class="px-4 py-3">{{ t('audit.user') }}</th>
                <th class="px-4 py-3">{{ t('audit.action') }}</th><th class="px-4 py-3">{{ t('audit.entity') }}</th>
                <th class="px-4 py-3">{{ t('audit.oldValue') }} → {{ t('audit.newValue') }}</th><th class="px-4 py-3">{{ t('audit.ipAddress') }}</th></tr></thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse ($logs as $a)
                    <tr>
                        <td class="px-4 py-3 text-[13px] text-neutral-500">{{ fdate($a->created_at, 'datetime') }}</td>
                        <td class="px-4 py-3 font-mono text-[12px] text-neutral-600">{{ $a->user?->email ?? 'system' }}</td>
                        <td class="px-4 py-3"><x-badge tone="neutral">{{ str_replace('_', ' ', $a->action) }}</x-badge></td>
                        <td class="px-4 py-3 text-[13px]">{{ $a->entity }} <span class="font-mono text-[11px] text-neutral-400">{{ $a->entity_id }}</span></td>
                        <td class="px-4 py-3 text-[13px]">
                            @if (data_get($a->old_values, 'value'))<span class="text-neutral-400 line-through">{{ data_get($a->old_values, 'value') }}</span> → @endif
                            <span class="font-medium text-neutral-700">{{ data_get($a->new_values, 'value') ?? '—' }}</span>
                        </td>
                        <td class="px-4 py-3 font-mono text-[12px] text-neutral-400">{{ $a->ip_address }}</td>
                    </tr>
                @empty<tr><td colspan="6"><x-empty :title="t('common.noData')" /></td></tr>@endforelse
            </tbody>
        </table>
        @if ($logs->hasPages())<div class="border-t border-neutral-100 px-4 py-3">{{ $logs->links() }}</div>@endif
    </x-card>
</x-layouts.app>

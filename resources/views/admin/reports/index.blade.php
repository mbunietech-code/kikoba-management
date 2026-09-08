@php
    $title = t('reports.title');
    $meta = [
        'membership' => ['users', 'primary'], 'shares' => ['chart-pie', 'tertiary'], 'savings' => ['banknotes', 'info'],
        'loans' => ['credit-card', 'primary'], 'profit' => ['arrow-trending-up', 'tertiary'], 'projects' => ['chart-bar', 'info'],
        'insurance' => ['shield-check', 'primary'], 'financial' => ['document-text', 'neutral'],
    ];
    $tint = ['primary'=>'bg-primary-50 text-primary-700','info'=>'bg-secondary-50 text-secondary-700','tertiary'=>'bg-tertiary-50 text-tertiary-700','neutral'=>'bg-neutral-100 text-neutral-600'];
@endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('reports.title')" :subtitle="t('reports.subtitle')" />
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($types as $type)
            @php [$icon, $tone] = $meta[$type]; @endphp
            <x-card class="k-row-anim flex h-full flex-col">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $tint[$tone] }}">
                    <x-dynamic-component :component="'heroicon-o-'.$icon" class="h-5 w-5" />
                </span>
                <h3 class="mt-3 font-display text-[15px] font-bold text-neutral-900">{{ t('reports.'.$type) }}</h3>
                <p class="mt-1 flex-1 text-[13px] text-neutral-500">{{ t('reports.asOf') }} {{ fdate(now()) }}</p>
                <a href="{{ route('admin.reports.download', $type) }}"
                   class="k-btn k-btn-outlined k-btn-sm mt-4 self-start inline-flex items-center gap-1.5">
                    <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                    {{ t('reports.generate') }} (CSV)
                </a>
            </x-card>
        @endforeach
    </div>
</x-layouts.app>

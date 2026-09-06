@php
    $title = t('reports.title');
    $reports = [
        ['membership', 'users', 'primary'], ['shares', 'chart-pie', 'tertiary'], ['savings', 'banknotes', 'info'],
        ['loans', 'credit-card', 'primary'], ['profit', 'arrow-trending-up', 'tertiary'], ['projects', 'chart-bar', 'info'],
        ['insurance', 'shield-check', 'primary'], ['financial', 'document-text', 'neutral'],
    ];
    $tint = ['primary'=>'bg-primary-50 text-primary-700','info'=>'bg-secondary-50 text-secondary-700','tertiary'=>'bg-tertiary-50 text-tertiary-700','neutral'=>'bg-neutral-100 text-neutral-600'];
@endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('reports.title')" :subtitle="t('reports.subtitle')" />
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($reports as $r)
            <x-card class="k-row-anim flex h-full flex-col">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $tint[$r[2]] }}">
                    <x-dynamic-component :component="'heroicon-o-'.$r[1]" class="h-5 w-5" />
                </span>
                <h3 class="mt-3 font-display text-[15px] font-bold text-neutral-900">{{ t('reports.'.$r[0]) }}</h3>
                <p class="mt-1 flex-1 text-[13px] text-neutral-500">{{ t('reports.asOf') }} {{ fdate(now()) }}</p>
                <x-btn variant="outlined" size="sm" icon="arrow-down-tray" class="mt-4 self-start"
                       onclick="window.toast('{{ t('reports.'.$r[0]) }} — {{ t('reports.generate') }} ✓')">{{ t('reports.generate') }}</x-btn>
            </x-card>
        @endforeach
    </div>
</x-layouts.app>

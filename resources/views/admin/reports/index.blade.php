@php $title = t('reports.title'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('reports.title')" :subtitle="t('reports.subtitle')" />
    <x-card flush>
        <ul class="divide-y divide-neutral-100">
            @foreach ($types as $type)
                <li class="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3.5">
                    <div class="min-w-0 flex-1">
                        <p class="text-[14px] font-semibold text-neutral-900">{{ t('reports.'.$type) }}</p>
                        <p class="mt-0.5 text-[13px] text-neutral-500">{{ t('reports.contents.'.$type) }}</p>
                    </div>
                    <a href="{{ route('admin.reports.download', $type) }}" class="k-btn k-btn-outlined k-btn-sm">
                        <x-heroicon-o-arrow-down-tray class="h-4 w-4" /> CSV
                    </a>
                </li>
            @endforeach
        </ul>
    </x-card>
    <p class="mt-3 text-[12.5px] text-neutral-500">{{ t('reports.asOfNote', ['date' => fdate(now())]) }}</p>
</x-layouts.app>

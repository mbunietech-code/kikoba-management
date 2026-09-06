@props(['title', 'subtitle' => null, 'back' => null, 'backLabel' => null])
<div class="mb-4 flex flex-wrap items-end justify-between gap-3">
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}" class="mb-1 inline-flex items-center gap-1 text-[12px] text-neutral-400 hover:text-neutral-600">
                <x-heroicon-o-arrow-left class="h-3.5 w-3.5" /> {{ $backLabel ?? t('common.back') }}
            </a>
        @endif
        <h1 class="font-display text-2xl font-bold text-neutral-900">{{ $title }}</h1>
        @if ($subtitle)<p class="mt-0.5 text-[13.5px] text-neutral-500">{{ $subtitle }}</p>@endif
    </div>
    @isset($actions)<div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>@endisset
</div>

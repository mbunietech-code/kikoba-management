@props(['label', 'value', 'hint' => null, 'delta' => null, 'deltaUp' => true, 'tone' => 'primary', 'icon' => null, 'i' => 0])
@php
    $tint = ['primary'=>'bg-primary-50 text-primary-700','secondary'=>'bg-secondary-50 text-secondary-700',
        'tertiary'=>'bg-tertiary-50 text-tertiary-700','neutral'=>'bg-neutral-100 text-neutral-600'][$tone] ?? 'bg-primary-50 text-primary-700';
@endphp
<div class="k-card k-row-anim p-4" style="animation-delay: {{ $i * 40 }}ms">
    <div class="flex items-start justify-between">
        <p class="text-[12.5px] font-medium text-neutral-500">{{ $label }}</p>
        @if ($icon)
            <span class="flex h-8 w-8 items-center justify-center rounded-lg {{ $tint }}">
                <x-dynamic-component :component="'heroicon-o-'.$icon" class="h-4 w-4" />
            </span>
        @endif
    </div>
    <p class="mt-2.5 font-display text-[21px] font-extrabold tracking-tight text-neutral-900">{{ $value }}</p>
    @if ($delta || $hint)
        <div class="mt-1.5 flex items-center gap-1.5 text-[12px]">
            @if ($delta)
                <span class="font-semibold {{ $deltaUp ? 'text-tertiary-700' : 'text-red-600' }}">{{ $deltaUp ? '▲' : '▼' }} {{ $delta }}</span>
            @endif
            @if ($hint)<span class="text-neutral-400">{{ $hint }}</span>@endif
        </div>
    @endif
</div>

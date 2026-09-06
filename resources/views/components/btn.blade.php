@props([
    'variant' => 'primary',   // primary | outlined | danger | ghost
    'size' => 'md',           // md | sm
    'href' => null,
    'icon' => null,
])
@php
    $classes = 'k-btn k-btn-'.$variant.($size === 'sm' ? ' k-btn-sm' : '');
    $tag = $href ? 'a' : 'button';
@endphp
<{{ $tag }} @if($href) href="{{ $href }}" @else type="{{ $attributes->get('type', 'button') }}" @endif
   {{ $attributes->except('type')->class($classes) }}>
    @if ($icon)<x-dynamic-component :component="'heroicon-o-'.$icon" class="h-4 w-4" />@endif
    {{ $slot }}
</{{ $tag }}>

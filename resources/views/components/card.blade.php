@props(['flush' => false, 'href' => null])
@php $tag = $href ? 'a' : 'div'; @endphp
<{{ $tag }} @if($href) href="{{ $href }}" @endif
   {{ $attributes->class(['k-card', 'p-4' => ! $flush, 'block transition hover:shadow-[var(--shadow-pop)]' => $href]) }}>
    {{ $slot }}
</{{ $tag }}>

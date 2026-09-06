@props(['label' => null, 'hint' => null, 'name' => null, 'error' => null])
@php $err = $error ?? ($name ? $errors->first($name) : null); @endphp
<label {{ $attributes->class('block') }}>
    @if ($label)
        <span class="mb-1.5 flex items-center justify-between">
            <span class="k-label">{{ $label }}</span>
            @if ($hint)<span class="text-[11px] text-neutral-400">{{ $hint }}</span>@endif
        </span>
    @endif
    {{ $slot }}
    @if ($err)<span class="mt-1 block text-[12px] text-red-600">{{ $err }}</span>@endif
</label>

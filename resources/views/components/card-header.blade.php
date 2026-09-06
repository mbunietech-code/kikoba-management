@props(['title', 'subtitle' => null])
<div {{ $attributes->class('flex items-start justify-between gap-3 px-1 pb-3') }}>
    <div>
        <p class="text-[15px] font-bold text-neutral-900">{{ $title }}</p>
        @if ($subtitle)<p class="mt-0.5 text-[12.5px] text-neutral-500">{{ $subtitle }}</p>@endif
    </div>
    @isset($action){{ $action }}@endisset
</div>

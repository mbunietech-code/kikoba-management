@props(['title', 'hint' => null, 'icon' => 'inbox'])
<div class="flex flex-col items-center px-6 py-12 text-center">
    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-neutral-100 text-neutral-400">
        <x-dynamic-component :component="'heroicon-o-'.$icon" class="h-6 w-6" />
    </span>
    <p class="mt-3 font-bold text-neutral-800">{{ $title }}</p>
    @if ($hint)<p class="mt-1 text-[13px] text-neutral-500">{{ $hint }}</p>@endif
    @isset($action)<div class="mt-4">{{ $action }}</div>@endisset
</div>

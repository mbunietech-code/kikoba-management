@props(['name', 'title' => null, 'size' => 'md'])
@php $w = ['sm'=>'max-w-sm','md'=>'max-w-lg','lg'=>'max-w-2xl'][$size] ?? 'max-w-lg'; @endphp
<div x-data="{ open: false }"
     x-on:open-modal.window="if ($event.detail === '{{ $name }}') open = true"
     x-on:close-modal.window="if ($event.detail === '{{ $name }}' || !$event.detail) open = false"
     x-on:keydown.escape.window="open = false"
     x-cloak>
    <div x-show="open" x-transition.opacity class="fixed inset-0 z-50 bg-neutral-900/40" x-on:click="open = false"></div>
    <div x-show="open" x-transition
         class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 pt-16">
        <div class="w-full {{ $w }} rounded-2xl bg-white shadow-[var(--shadow-pop)]" x-on:click.stop>
            <div class="flex items-center justify-between border-b border-neutral-100 px-5 py-4">
                <p class="font-display text-[15px] font-bold text-neutral-900">{{ $title }}</p>
                <button x-on:click="open = false" class="text-neutral-400 hover:text-neutral-700">
                    <x-heroicon-o-x-mark class="h-5 w-5" />
                </button>
            </div>
            <div class="px-5 py-5">{{ $slot }}</div>
            @isset($footer)
                <div class="flex justify-end gap-2 border-t border-neutral-100 px-5 py-4">{{ $footer }}</div>
            @endisset
        </div>
    </div>
</div>

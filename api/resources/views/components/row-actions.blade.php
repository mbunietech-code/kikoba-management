@props(['edit' => null, 'delete' => null, 'deleteLabel' => null, 'confirm' => null])
<div x-data="{ menu: false, confirming: false }" class="relative inline-block text-left" x-on:click.stop>
    <button x-on:click="menu = !menu" x-on:click.outside="menu = false"
            class="flex h-8 w-8 items-center justify-center rounded-lg text-neutral-400 hover:bg-neutral-100 hover:text-neutral-700">
        <x-heroicon-o-ellipsis-horizontal class="h-4 w-4" />
    </button>
    <div x-show="menu" x-transition x-cloak
         class="absolute right-0 z-20 mt-1 w-40 overflow-hidden rounded-xl border border-neutral-200 bg-white py-1 shadow-[var(--shadow-pop)]">
        @if ($edit)
            <a href="{{ $edit }}" class="flex items-center gap-2 px-3 py-2 text-[13px] text-neutral-700 hover:bg-neutral-50">
                <x-heroicon-o-pencil-square class="h-4 w-4" /> {{ t('common.edit') }}
            </a>
        @endif
        {{ $extra ?? '' }}
        @if ($delete)
            <button x-on:click="menu = false; confirming = true"
                    class="flex w-full items-center gap-2 px-3 py-2 text-[13px] text-red-600 hover:bg-red-50">
                <x-heroicon-o-trash class="h-4 w-4" /> {{ $deleteLabel ?? t('common.delete') }}
            </button>
        @endif
    </div>

    @if ($delete)
        <div x-show="confirming" x-cloak class="fixed inset-0 z-50 flex items-start justify-center bg-neutral-900/40 p-4 pt-24" x-on:click="confirming = false">
            <div class="w-full max-w-sm rounded-2xl bg-white p-5 shadow-[var(--shadow-pop)]" x-on:click.stop>
                <p class="font-display font-bold text-neutral-900">{{ $deleteLabel ?? t('common.delete') }}</p>
                <p class="mt-2 text-[13px] text-neutral-600">{{ $confirm ?? t('common.confirmDelete') }}</p>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" x-on:click="confirming = false" class="k-btn k-btn-outlined k-btn-sm">{{ t('common.cancel') }}</button>
                    <form method="POST" action="{{ $delete }}">
                        @csrf @method('DELETE')
                        <button class="k-btn k-btn-danger k-btn-sm">{{ $deleteLabel ?? t('common.delete') }}</button>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

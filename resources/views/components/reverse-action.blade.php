@props(['action', 'summary' => null, 'size' => 'sm'])
{{-- Reverse button + dialog asking for the reason. Posts `reason` to $action. --}}
<div x-data="{ open: false, busy: false }" class="inline-block text-left" x-on:click.stop x-on:keydown.escape.window="open = false">
    <button type="button" x-on:click="open = true" class="k-btn k-btn-outlined {{ $size === 'sm' ? 'k-btn-sm' : '' }}">{{ t('common.reverse') }}</button>
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-start justify-center bg-neutral-900/40 p-4 pt-24" x-on:click="open = false">
        <form method="POST" action="{{ $action }}" class="w-full max-w-sm rounded-lg bg-white p-5 text-left whitespace-normal shadow-[var(--shadow-pop)]"
              x-on:click.stop x-on:submit="busy = true">
            @csrf
            <p class="font-display font-bold text-neutral-900">{{ t('common.reverse') }}</p>
            @if ($summary)<p class="mt-1 text-[13px] text-neutral-600">{{ $summary }}</p>@endif
            <p class="mt-2 text-[13px] text-neutral-600">{{ t('transactions.reversal.explain') }}</p>
            <label class="mt-3 block">
                <span class="k-label">{{ t('transactions.reversal.reason') }}</span>
                <textarea name="reason" required maxlength="255" rows="2" class="k-input mt-1.5"></textarea>
            </label>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" x-on:click="open = false" class="k-btn k-btn-outlined k-btn-sm">{{ t('common.cancel') }}</button>
                <button class="k-btn k-btn-danger k-btn-sm" x-bind:disabled="busy">{{ t('common.reverse') }}</button>
            </div>
        </form>
    </div>
</div>

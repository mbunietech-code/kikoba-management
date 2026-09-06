<div x-data="{
        items: [],
        add(message, tone) {
            const id = Date.now() + Math.random();
            this.items.push({ id, message, tone });
            setTimeout(() => this.items = this.items.filter(i => i.id !== id), 3500);
        }
     }"
     x-on:k-toast.window="add($event.detail.message, $event.detail.tone)"
     x-init="@if (session('toast')) add(@js(session('toast')), @js(session('toast_tone', 'success'))) @endif"
     class="fixed bottom-4 left-1/2 z-[60] flex -translate-x-1/2 flex-col items-center gap-2"
     x-cloak>
    <template x-for="item in items" :key="item.id">
        <div x-transition
             class="rounded-xl px-4 py-2.5 text-[13px] font-medium text-white shadow-lg"
             :class="item.tone === 'error' ? 'bg-red-600' : 'bg-neutral-900'"
             x-text="item.message"></div>
    </template>
</div>

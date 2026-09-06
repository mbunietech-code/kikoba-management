@php
    $title = t('notifications.title');
    $unread = $notifications->getCollection()->whereNull('read_at')->count();
    $icons = ['sms'=>'chat-bubble-left-right','email'=>'envelope','push'=>'device-phone-mobile','whatsapp'=>'chat-bubble-left-right','in_app'=>'bell'];
@endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('notifications.title')" :subtitle="$unread.' '.t('notifications.unread')">
        <x-slot:actions>
            <form method="POST" action="{{ route('admin.notifications.readAll') }}">@csrf<button class="k-btn k-btn-outlined">{{ t('notifications.markAllRead') }}</button></form>
            <x-btn icon="paper-airplane" x-data x-on:click="$dispatch('open-modal','announce')">{{ t('notifications.sendAnnouncement') }}</x-btn>
        </x-slot:actions>
    </x-page-header>
    <x-card flush>
        <ul class="divide-y divide-neutral-100">
            @forelse ($notifications as $n)
                <li class="flex gap-3 px-5 py-4 {{ ! $n->read_at ? 'bg-primary-50/40' : '' }}">
                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $n->read_at ? 'bg-neutral-100 text-neutral-400' : 'bg-primary-100 text-primary-700' }}">
                        <x-dynamic-component :component="'heroicon-o-'.($icons[$n->channel] ?? 'bell')" class="h-4 w-4" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[14px] font-semibold text-neutral-900">{{ $n->title }}</p>
                        <p class="mt-0.5 text-[13px] text-neutral-600">{{ $n->message }}</p>
                        <div class="mt-1.5 flex items-center gap-2">
                            <x-badge tone="neutral">{{ t('notifications.channels.'.$n->channel) }}</x-badge>
                            <span class="text-[12px] text-neutral-400">{{ $n->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.notifications.destroy', $n) }}" class="self-center">@csrf @method('DELETE')
                        <button class="rounded-lg p-1.5 text-neutral-300 hover:bg-red-50 hover:text-red-600"><x-heroicon-o-trash class="h-4 w-4" /></button>
                    </form>
                </li>
            @empty
                <li><x-empty :title="t('common.noData')" /></li>
            @endforelse
        </ul>
        @if ($notifications->hasPages())<div class="border-t border-neutral-100 px-4 py-3">{{ $notifications->links() }}</div>@endif
    </x-card>

    <x-modal name="announce" :title="t('notifications.sendAnnouncement')">
        <form method="POST" action="{{ route('admin.notifications.announce') }}" class="grid gap-4">@csrf
            <x-field :label="t('common.name')"><x-input name="title" /></x-field>
            <x-field :label="t('common.description')"><x-textarea name="message"></x-textarea></x-field>
            <x-field :label="t('notifications.channel')"><x-select name="channel">@foreach (['in_app','sms','email','push','whatsapp'] as $c)<option value="{{ $c }}">{{ t("notifications.channels.$c") }}</option>@endforeach</x-select></x-field>
            <button class="k-btn k-btn-primary">{{ t('common.submit') }}</button>
        </form>
    </x-modal>
</x-layouts.app>

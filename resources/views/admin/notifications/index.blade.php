@php $title = t('notifications.title'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('notifications.title')" :subtitle="$unread.' '.t('notifications.unread')">
        <x-slot:actions>
            @if ($unread)<form method="POST" action="{{ route('admin.notifications.readAll') }}">@csrf<button class="k-btn k-btn-outlined">{{ t('notifications.markAllRead') }}</button></form>@endif
            @can('notifications.send')<x-btn icon="megaphone" x-data x-on:click="$dispatch('open-modal','announce')">{{ t('notifications.sendAnnouncement') }}</x-btn>@endcan
        </x-slot:actions>
    </x-page-header>
    <x-card flush>
        <ul class="divide-y divide-neutral-100">
            @forelse ($notifications as $n)
                <li class="flex gap-3 px-4 py-3.5">
                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $n->read_at ? 'bg-transparent' : 'bg-primary-600' }}" aria-hidden="true"></span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[14px] {{ $n->read_at ? 'font-medium text-neutral-700' : 'font-semibold text-neutral-900' }}">{{ $n->title }}</p>
                        <p class="mt-0.5 whitespace-pre-line text-[13px] text-neutral-600">{{ $n->message }}</p>
                        <p class="mt-1 text-[12px] text-neutral-400">
                            {{ $n->member ? t('notifications.toMember', ['name' => $n->member->full_name]) : t('notifications.toEveryone') }}
                            · {{ $n->created_at->diffForHumans() }}
                        </p>
                    </div>
                    @can('notifications.send')
                        <div class="self-center"><x-row-actions :delete="route('admin.notifications.destroy', $n)" /></div>
                    @endcan
                </li>
            @empty
                <li><x-empty :title="t('notifications.emptyTitle')" icon="bell" /></li>
            @endforelse
        </ul>
        @if ($notifications->hasPages())<div class="border-t border-neutral-100 px-4 py-3">{{ $notifications->links() }}</div>@endif
    </x-card>

    @can('notifications.send')
        <x-modal name="announce" :title="t('notifications.sendAnnouncement')">
            <form method="POST" action="{{ route('admin.notifications.announce') }}" class="grid gap-4" x-data="{ busy: false }" x-on:submit="busy = true">@csrf
                <x-field :label="t('notifications.subject')" name="title"><x-input name="title" required maxlength="120" :value="old('title')" /></x-field>
                <x-field :label="t('notifications.message')" name="message"><x-textarea name="message" rows="4" required maxlength="2000">{{ old('message') }}</x-textarea></x-field>
                <p class="rounded-md bg-neutral-50 px-3 py-2 text-[12.5px] text-neutral-600">{{ t('notifications.inAppOnly') }}</p>
                <div class="flex justify-end gap-2">
                    <button type="button" class="k-btn k-btn-outlined" x-on:click="$dispatch('close-modal','announce')">{{ t('common.cancel') }}</button>
                    <button class="k-btn k-btn-primary" x-bind:disabled="busy">{{ t('notifications.post') }}</button>
                </div>
            </form>
        </x-modal>
    @endcan
</x-layouts.app>

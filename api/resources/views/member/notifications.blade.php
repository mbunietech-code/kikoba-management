@php $title = t('nav.notifications'); $icons = ['sms'=>'chat-bubble-left-right','email'=>'envelope','push'=>'device-phone-mobile','in_app'=>'bell']; @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('nav.notifications')" :subtitle="$notifications->getCollection()->whereNull('read_at')->count() . ' ' . t('notifications.unread')" />
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
                        <p class="mt-1.5 text-[12px] text-neutral-400">{{ $n->created_at->diffForHumans() }}</p>
                    </div>
                </li>
            @empty<li><x-empty :title="t('common.noData')" /></li>@endforelse
        </ul>
        @if ($notifications->hasPages())<div class="border-t border-neutral-100 px-4 py-3">{{ $notifications->links() }}</div>@endif
    </x-card>
</x-layouts.app>

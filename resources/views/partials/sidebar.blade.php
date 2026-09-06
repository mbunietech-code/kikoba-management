<nav class="flex h-full flex-col gap-6 px-3 py-5">
    <div class="flex items-center gap-2.5 px-3">
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary-800 font-display text-[13px] font-bold text-white">{{ initials($org->name) }}</span>
        <div class="min-w-0">
            <p class="truncate font-display text-[15px] font-bold leading-tight text-neutral-900">{{ $org->name }}</p>
            <p class="text-[11px] text-neutral-400">{{ t('app.tagline') }}</p>
        </div>
    </div>

    <div class="flex flex-1 flex-col gap-5">
        @foreach ($sections as $section)
            @php
                $items = collect($section['items'])->filter(fn ($it) => empty($it['can']) || auth()->user()?->can($it['can']));
            @endphp
            @if ($items->isNotEmpty())
                <div>
                    <p class="mb-1.5 px-3 text-[11px] font-semibold uppercase tracking-wider text-neutral-400">{{ t($section['title']) }}</p>
                    <ul class="flex flex-col gap-0.5">
                        @foreach ($items as $it)
                            @php $active = request()->routeIs($it['route']) || request()->routeIs(str($it['route'])->beforeLast('.').'.*'); @endphp
                            <li>
                                <a href="{{ route($it['route']) }}"
                                   class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-[13.5px] font-medium transition
                                          {{ $active ? 'bg-primary-50 text-primary-800' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900' }}">
                                    <x-dynamic-component :component="'heroicon-o-'.$it['icon']" class="h-[18px] w-[18px] {{ $active ? 'text-primary-700' : 'text-neutral-400' }}" />
                                    {{ t($it['label']) }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endforeach
    </div>
</nav>

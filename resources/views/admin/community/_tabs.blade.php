<div class="mb-4 flex gap-1 border-b border-neutral-200">
    @foreach (['contributions' => t('community.tabContributions'), 'groups' => t('community.groups')] as $key => $label)
        <a href="{{ route('admin.community.index', $key === 'groups' ? ['tab' => 'groups'] : []) }}"
           class="relative px-3 py-2.5 text-[13.5px] font-medium {{ $current === $key ? 'text-primary-800' : 'text-neutral-500 hover:text-neutral-800' }}">
            {{ $label }}
            @if ($current === $key)<span class="absolute inset-x-2 -bottom-px h-0.5 rounded-full bg-primary-700"></span>@endif
        </a>
    @endforeach
</div>

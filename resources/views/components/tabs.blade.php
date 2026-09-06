@props(['items', 'current', 'param' => 'tab'])
{{-- items: [ ['key'=>'overview','label'=>'Overview','count'=>3], ... ] --}}
<div class="flex gap-1 overflow-x-auto border-b border-neutral-200 px-1">
    @foreach ($items as $it)
        @php $active = $current === $it['key']; @endphp
        <a href="{{ request()->fullUrlWithQuery([$param => $it['key']]) }}"
           class="relative whitespace-nowrap px-3 py-2.5 text-[13px] font-medium transition
                  {{ $active ? 'text-primary-700' : 'text-neutral-500 hover:text-neutral-700' }}">
            {{ $it['label'] }}
            @if (isset($it['count']))
                <span class="ml-1 rounded-full bg-neutral-100 px-1.5 py-0.5 text-[10px] text-neutral-500">{{ $it['count'] }}</span>
            @endif
            @if ($active)<span class="absolute inset-x-2 -bottom-px h-0.5 rounded-full bg-primary-600"></span>@endif
        </a>
    @endforeach
</div>

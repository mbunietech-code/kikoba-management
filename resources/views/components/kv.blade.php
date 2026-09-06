@props(['items', 'cols' => 2])
{{-- items: [ ['label' => '...', 'value' => '...' (may be html)] ] --}}
<dl class="grid gap-x-6 gap-y-4 sm:grid-cols-{{ $cols }}">
    @foreach ($items as $it)
        <div>
            <dt class="text-[10.5px] font-semibold uppercase tracking-wide text-neutral-400">{{ $it['label'] }}</dt>
            <dd class="mt-0.5 text-[13.5px] text-neutral-800">{!! $it['value'] !!}</dd>
        </div>
    @endforeach
</dl>

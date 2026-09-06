@props(['filters' => []])
{{-- filters: [ ['name' => 'status', 'value' => request('status'), 'options' => ['all' => 'All', ...]] ] --}}
<form method="GET" class="mb-3 flex flex-wrap items-center gap-2">
    @foreach (request()->except(['q', 'page', ...collect($filters)->pluck('name')->all()]) as $k => $v)
        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
    @endforeach
    <div class="relative min-w-[200px] flex-1">
        <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400" />
        <input name="q" value="{{ request('q') }}" placeholder="{{ t('common.search') }}…"
               class="k-input pl-9" onchange="this.form.submit()">
    </div>
    @foreach ($filters as $f)
        <select name="{{ $f['name'] }}" onchange="this.form.submit()" class="k-input w-auto min-w-[130px]">
            @foreach ($f['options'] as $val => $label)
                <option value="{{ $val }}" @selected(($f['value'] ?? '') == $val)>{{ $label }}</option>
            @endforeach
        </select>
    @endforeach
</form>

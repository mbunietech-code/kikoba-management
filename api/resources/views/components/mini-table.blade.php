@props(['head' => [], 'rows' => [], 'html' => false, 'align' => []])
<div class="overflow-x-auto">
    <table class="w-full text-sm">
        @if ($head)
            <thead>
                <tr class="border-b border-neutral-200 text-left text-[11px] uppercase tracking-wide text-neutral-500">
                    @foreach ($head as $i => $h)
                        <th class="px-3 py-2.5 {{ ($align[$i] ?? '') === 'right' ? 'text-right' : '' }}">{{ $h }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody class="divide-y divide-neutral-100">
            @forelse ($rows as $row)
                <tr class="hover:bg-neutral-50/60">
                    @foreach ((array) $row as $i => $cell)
                        <td class="px-3 py-2.5 text-[13px] {{ ($align[$i] ?? '') === 'right' ? 'text-right' : '' }}">
                            @if ($html) {!! $cell !!} @else {{ $cell }} @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ max(1, count($head)) }}" class="px-3 py-8 text-center text-sm text-neutral-400">{{ t('common.noData') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

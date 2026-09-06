@props(['value' => 0, 'tone' => 'primary', 'showLabel' => false])
@php
    $v = max(0, min(100, $value));
    $c = ['primary'=>'#0d9488','tertiary'=>'#16a34a','success'=>'#16a34a','info'=>'#2563eb','danger'=>'#dc2626'][$tone] ?? '#0d9488';
@endphp
<div class="flex items-center gap-2">
    <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-neutral-100">
        <div class="h-full rounded-full transition-all" style="width: {{ $v }}%; background: {{ $c }}"></div>
    </div>
    @if ($showLabel)<span class="w-9 text-right text-[11.5px] font-semibold text-neutral-500">{{ round($v) }}%</span>@endif
</div>

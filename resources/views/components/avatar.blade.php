@props(['name', 'color' => null, 'size' => 'md'])
@php $s = ['xs'=>'h-7 w-7 text-[10px]','sm'=>'h-8 w-8 text-[11px]','md'=>'h-10 w-10 text-[13px]','lg'=>'h-16 w-16 text-lg'][$size] ?? 'h-10 w-10 text-[13px]'; @endphp
<span class="inline-flex shrink-0 items-center justify-center rounded-full font-bold text-white {{ $s }}" style="background: {{ $color ?: '#115e59' }}">{{ initials($name) }}</span>

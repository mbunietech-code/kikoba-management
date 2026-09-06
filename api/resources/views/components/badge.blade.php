@props(['tone' => 'neutral', 'dot' => false])
@php
    $bg = ['neutral'=>'bg-neutral-100 text-neutral-600','primary'=>'bg-primary-50 text-primary-700',
        'success'=>'bg-tertiary-50 text-tertiary-700','warning'=>'bg-amber-50 text-amber-700',
        'danger'=>'bg-red-50 text-red-700','info'=>'bg-secondary-50 text-secondary-700',
        'purple'=>'bg-violet-50 text-violet-700'][$tone] ?? 'bg-neutral-100 text-neutral-600';
    $dotc = ['neutral'=>'bg-neutral-500','primary'=>'bg-primary-600','success'=>'bg-tertiary-600',
        'warning'=>'bg-amber-600','danger'=>'bg-red-600','info'=>'bg-secondary-600','purple'=>'bg-violet-600'][$tone] ?? 'bg-neutral-500';
@endphp
<span {{ $attributes->class("inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11.5px] font-semibold $bg") }}>
    @if ($dot)<span class="h-1.5 w-1.5 rounded-full {{ $dotc }}"></span>@endif
    {{ $slot }}
</span>

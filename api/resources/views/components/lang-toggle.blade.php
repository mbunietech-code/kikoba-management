@php $locale = app()->getLocale(); @endphp
<div class="inline-flex items-center gap-0.5 rounded-lg border border-neutral-200 bg-neutral-50 p-0.5">
    @foreach (['en' => 'EN', 'sw' => 'SW'] as $code => $label)
        <a href="{{ route('locale', $code) }}"
           class="rounded-md px-2.5 py-1 text-xs font-bold transition
                  {{ $locale === $code ? 'bg-white text-primary-700 shadow-sm' : 'text-neutral-500 hover:text-neutral-700' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

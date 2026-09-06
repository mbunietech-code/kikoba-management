@php $title = t('roles.title'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('roles.title')" :subtitle="t('roles.subtitle')" />
    <div class="grid gap-4 lg:grid-cols-[260px_1fr]">
        <div class="flex flex-col gap-2">
            @foreach ($roles as $r)
                <a href="{{ route('admin.roles.index', ['role' => $r->name]) }}"
                   class="flex items-center justify-between rounded-xl border px-4 py-3 text-left transition
                          {{ $active === $r->name ? 'border-primary-500 bg-primary-50 ring-4 ring-primary-100' : 'border-neutral-200 bg-white hover:border-neutral-300' }}">
                    <span>
                        <span class="block text-[14px] font-semibold text-neutral-800">{{ t('users.roles.'.$r->name) }}</span>
                        <span class="block text-[12px] text-neutral-400">{{ $counts[$r->name] ?? 0 }} {{ t('roles.members') }}</span>
                    </span>
                </a>
            @endforeach
        </div>

        <x-card>
            <form method="POST" action="{{ route('admin.roles.update', $current->name) }}">
                @csrf @method('PUT')
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="font-display text-[15px] font-bold text-neutral-900">{{ t('roles.permissions') }} — {{ t('users.roles.'.$current->name) }}</h3>
                    <button class="k-btn k-btn-primary k-btn-sm">{{ t('common.saveChanges') }}</button>
                </div>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($permissions as $perm)
                        <label class="flex items-center gap-3 rounded-lg border border-neutral-200 px-3 py-2.5">
                            <input type="checkbox" name="permissions[]" value="{{ $perm }}" class="h-4 w-4 rounded border-neutral-300 text-primary-600"
                                   @checked($current->permissions->contains('name', $perm))>
                            <span class="font-mono text-[12.5px] text-neutral-600">{{ $perm }}</span>
                        </label>
                    @endforeach
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.app>

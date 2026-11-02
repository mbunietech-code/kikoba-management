@php $title = t('users.title'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('users.title')" :subtitle="t('users.subtitle')">
        <x-slot:actions>@can('users.manage')<x-btn icon="user-plus" x-data x-on:click="$dispatch('open-modal','user')">{{ t('users.addUser') }}</x-btn>@endcan</x-slot:actions>
    </x-page-header>
    <x-card flush>
        <table class="w-full text-sm">
            <thead><tr class="border-b border-neutral-200 text-left text-[11px] uppercase tracking-wide text-neutral-500">
                <th class="px-4 py-3">{{ t('common.name') }}</th><th class="px-4 py-3">{{ t('common.phone') }}</th>
                <th class="px-4 py-3">{{ t('users.role') }}</th><th class="px-4 py-3">{{ t('users.lastLogin') }}</th>
                <th class="px-4 py-3">{{ t('common.status') }}</th><th></th></tr></thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($users as $u)
                    @php $role = $u->roles->first()?->name ?? 'member'; $editable = in_array($role, $assignableRoles, true) && $u->id !== auth()->id(); @endphp
                    <tr class="hover:bg-neutral-50">
                        <td class="px-4 py-3"><div class="flex items-center gap-2.5"><x-avatar :name="$u->name" color="#334155" size="sm" /><span><span class="block text-[13.5px] font-medium">{{ $u->name }}</span><span class="block text-[11px] text-neutral-400">{{ $u->email }}</span></span></div></td>
                        <td class="whitespace-nowrap px-4 py-3 text-[13px] text-neutral-500">{{ $u->phone ?: '—' }}</td>
                        <td class="px-4 py-3"><x-badge :tone="$role === 'member' ? 'neutral' : 'primary'">{{ t('users.roles.'.$role) }}</x-badge></td>
                        <td class="whitespace-nowrap px-4 py-3 text-[13px] text-neutral-500">{{ $u->last_login_at?->diffForHumans() ?? t('users.never') }}</td>
                        <td class="px-4 py-3"><x-status :status="$u->status" :label="t('users.statuses.'.$u->status)" /></td>
                        <td class="px-4 py-3 text-right">
                            @if ($editable && auth()->user()->can('users.manage'))
                                <x-row-actions :delete="$role !== 'super_admin' ? route('admin.users.destroy', $u) : null" :confirm="t('users.deleteConfirm', ['name' => $u->name])">
                                    <x-slot:extra>
                                        <button type="button" class="flex w-full items-center gap-2 px-3 py-2 text-[13px] text-neutral-700 hover:bg-neutral-50"
                                                x-on:click="menu=false; $dispatch('open-modal','user-{{ $u->id }}')">
                                            <x-heroicon-o-pencil-square class="h-4 w-4" /> {{ t('common.edit') }}
                                        </button>
                                    </x-slot:extra>
                                </x-row-actions>
                                <x-modal name="user-{{ $u->id }}" :title="t('common.edit').' — '.$u->name">
                                    <form method="POST" action="{{ route('admin.users.update', $u) }}" class="grid gap-4 text-left">@csrf @method('PUT')
                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <x-field :label="t('common.name')"><x-input name="name" required :value="$u->name" /></x-field>
                                            <x-field :label="t('common.phone')"><x-input name="phone" :value="$u->phone" /></x-field>
                                            <x-field :label="t('users.role')"><x-select name="role">@foreach ($assignableRoles as $r)<option value="{{ $r }}" @selected($role === $r)>{{ t("users.roles.$r") }}</option>@endforeach</x-select></x-field>
                                            <x-field :label="t('common.status')"><x-select name="status">@foreach (['active','suspended'] as $s)<option value="{{ $s }}" @selected($u->status === $s)>{{ t('users.statuses.'.$s) }}</option>@endforeach</x-select></x-field>
                                        </div>
                                        <div class="rounded-md border border-neutral-200 p-3">
                                            <p class="text-[13px] font-semibold text-neutral-800">{{ t('users.resetPassword') }}</p>
                                            <p class="mt-0.5 text-[12px] text-neutral-500">{{ t('users.resetPasswordHint') }}</p>
                                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                                <x-input type="password" name="password" autocomplete="new-password" minlength="8" :placeholder="t('account.newPassword')" />
                                                <x-input type="password" name="password_confirmation" autocomplete="new-password" minlength="8" :placeholder="t('account.confirmPassword')" />
                                            </div>
                                        </div>
                                        <div class="flex justify-end gap-2">
                                            <button type="button" class="k-btn k-btn-outlined" x-on:click="$dispatch('close-modal','user-{{ $u->id }}')">{{ t('common.cancel') }}</button>
                                            <button class="k-btn k-btn-primary">{{ t('common.saveChanges') }}</button>
                                        </div>
                                    </form>
                                </x-modal>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-card>

    @can('users.manage')
        <x-modal name="user" :title="t('users.addUser')">
            <form method="POST" action="{{ route('admin.users.store') }}" class="grid gap-4">@csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field :label="t('common.name')" name="name"><x-input name="name" required :value="old('name')" /></x-field>
                    <x-field :label="t('users.role')" name="role"><x-select name="role">@foreach ($assignableRoles as $r)<option value="{{ $r }}" @selected(old('role', 'treasurer') === $r)>{{ t("users.roles.$r") }}</option>@endforeach</x-select></x-field>
                    <x-field :label="t('common.email')" name="email"><x-input type="email" name="email" required :value="old('email')" /></x-field>
                    <x-field :label="t('common.phone')" name="phone"><x-input name="phone" :value="old('phone')" /></x-field>
                    <x-field :label="t('users.initialPassword')" :hint="t('account.minChars')" name="password"><x-input type="password" name="password" autocomplete="new-password" minlength="8" required /></x-field>
                    <x-field :label="t('account.confirmPassword')" name="password_confirmation"><x-input type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required /></x-field>
                </div>
                <p class="text-[12.5px] text-neutral-500">{{ t('users.initialPasswordHint') }}</p>
                <div class="flex justify-end gap-2">
                    <button type="button" class="k-btn k-btn-outlined" x-on:click="$dispatch('close-modal','user')">{{ t('common.cancel') }}</button>
                    <button class="k-btn k-btn-primary">{{ t('users.addUser') }}</button>
                </div>
            </form>
        </x-modal>
        @if ($errors->hasAny(['name', 'email', 'phone', 'role', 'password']) && old('email'))
            <div x-data x-init="$nextTick(() => $dispatch('open-modal', 'user'))"></div>
        @endif
    @endcan
</x-layouts.app>

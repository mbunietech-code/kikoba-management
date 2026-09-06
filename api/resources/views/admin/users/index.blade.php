@php $title = t('users.title'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('users.title')" :subtitle="t('users.subtitle')">
        <x-slot:actions><x-btn icon="user-plus" x-data x-on:click="$dispatch('open-modal','user')">{{ t('users.addUser') }}</x-btn></x-slot:actions>
    </x-page-header>
    <x-card flush>
        <table class="w-full text-sm">
            <thead><tr class="border-b border-neutral-200 text-left text-[11px] uppercase tracking-wide text-neutral-500">
                <th class="px-4 py-3">{{ t('common.name') }}</th><th class="px-4 py-3">{{ t('common.phone') }}</th>
                <th class="px-4 py-3">{{ t('users.role') }}</th><th class="px-4 py-3">{{ t('users.lastLogin') }}</th>
                <th class="px-4 py-3">{{ t('common.status') }}</th><th></th></tr></thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($users as $u)
                    <tr class="hover:bg-neutral-50">
                        <td class="px-4 py-3"><div class="flex items-center gap-2.5"><x-avatar :name="$u->name" color="#0f172a" size="sm" /><span><span class="block text-[13.5px] font-medium">{{ $u->name }}</span><span class="block text-[11px] text-neutral-400">{{ $u->email }}</span></span></div></td>
                        <td class="px-4 py-3 text-[13px] text-neutral-500">{{ $u->phone }}</td>
                        <td class="px-4 py-3"><x-badge tone="primary">{{ t('users.roles.'.($u->roles->first()?->name ?? 'member')) }}</x-badge></td>
                        <td class="px-4 py-3 text-[13px] text-neutral-500">{{ $u->last_login_at?->diffForHumans() ?? t('users.never') }}</td>
                        <td class="px-4 py-3"><x-status :status="$u->status" /></td>
                        <td class="px-4 py-3 text-right">
                            @unless ($u->hasRole('super_admin'))
                                <x-row-actions :delete="route('admin.users.destroy', $u)">
                                    <x-slot:extra>
                                        <button type="button" class="flex w-full items-center gap-2 px-3 py-2 text-[13px] text-neutral-700 hover:bg-neutral-50"
                                                x-on:click="menu=false; $dispatch('open-modal','user-{{ $u->id }}')">
                                            <x-heroicon-o-pencil-square class="h-4 w-4" /> {{ t('common.edit') }}
                                        </button>
                                    </x-slot:extra>
                                </x-row-actions>
                                <x-modal name="user-{{ $u->id }}" :title="t('common.edit').' — '.$u->name">
                                    <form method="POST" action="{{ route('admin.users.update', $u) }}" class="grid gap-4">@csrf @method('PUT')
                                        <x-field :label="t('common.name')"><x-input name="name" :value="$u->name" /></x-field>
                                        <x-field :label="t('common.phone')"><x-input name="phone" :value="$u->phone" /></x-field>
                                        <x-field :label="t('users.role')"><x-select name="role">@foreach (['admin','treasurer','accountant','loan_officer'] as $r)<option value="{{ $r }}" @selected($u->roles->first()?->name === $r)>{{ t("users.roles.$r") }}</option>@endforeach</x-select></x-field>
                                        <x-field :label="t('common.status')"><x-select name="status"><option value="active" @selected($u->status==='active')>Active</option><option value="suspended" @selected($u->status==='suspended')>Suspended</option></x-select></x-field>
                                        <button class="k-btn k-btn-primary">{{ t('common.save') }}</button>
                                    </form>
                                </x-modal>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-card>

    <x-modal name="user" :title="t('users.addUser')">
        <form method="POST" action="{{ route('admin.users.store') }}" class="grid gap-4">@csrf
            <x-field :label="t('common.name')"><x-input name="name" /></x-field>
            <x-field :label="t('common.email')"><x-input type="email" name="email" /></x-field>
            <x-field :label="t('common.phone')"><x-input name="phone" /></x-field>
            <x-field :label="t('users.role')"><x-select name="role">@foreach (['admin','treasurer','accountant','loan_officer'] as $r)<option value="{{ $r }}">{{ t("users.roles.$r") }}</option>@endforeach</x-select></x-field>
            <button class="k-btn k-btn-primary">{{ t('common.create') }}</button>
        </form>
    </x-modal>
</x-layouts.app>

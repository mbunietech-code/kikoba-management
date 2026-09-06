@php $title = t('members.title'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('members.title')" :subtitle="t('members.subtitle')">
        <x-slot:actions>
            <x-btn variant="outlined" icon="arrow-down-tray">{{ t('common.export') }}</x-btn>
            <x-btn :href="route('admin.members.create')" icon="user-plus">{{ t('members.addMember') }}</x-btn>
        </x-slot:actions>
    </x-page-header>

    @include('partials.toolbar', ['filters' => [
        ['name' => 'status', 'value' => request('status'), 'options' => ['' => t('common.all').' — '.t('common.status')] + collect(['active','pending','suspended','inactive','deceased'])->mapWithKeys(fn($s) => [$s => t("members.status.$s")])->all()],
        ['name' => 'gender', 'value' => request('gender'), 'options' => ['' => t('common.all').' — '.t('members.gender'), 'male' => t('members.male'), 'female' => t('members.female')]],
    ]])

    <x-card flush>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-neutral-200 text-left text-[11px] uppercase tracking-wide text-neutral-500">
                        <th class="px-4 py-3">{{ t('members.fullName') }}</th>
                        <th class="px-4 py-3">{{ t('common.phone') }}</th>
                        <th class="px-4 py-3 text-right">{{ t('nav.shares') }}</th>
                        <th class="px-4 py-3 text-right">{{ t('nav.savings') }}</th>
                        <th class="px-4 py-3">{{ t('members.joined') }}</th>
                        <th class="px-4 py-3">{{ t('common.status') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse ($members as $m)
                        <tr class="hover:bg-neutral-50" onclick="window.location='{{ route('admin.members.show', $m) }}'" style="cursor:pointer">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <x-avatar :name="$m->full_name" :color="$m->avatar_color" size="sm" />
                                    <div>
                                        <div class="text-[13.5px] font-medium text-neutral-800">{{ $m->full_name }}</div>
                                        <div class="text-[11px] text-neutral-400">{{ $m->member_number }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-[13px] text-neutral-500">{{ $m->phone }}</td>
                            <td class="px-4 py-3 text-right">{{ money($m->shares_value ?? 0, true) }}</td>
                            <td class="px-4 py-3 text-right">{{ money($m->savingsAccount?->balance ?? 0, true) }}</td>
                            <td class="px-4 py-3 text-[13px] text-neutral-500">{{ fdate($m->registration_date) }}</td>
                            <td class="px-4 py-3"><x-status :status="$m->status" :label="t('members.status.'.$m->status)" /></td>
                            <td class="px-4 py-3 text-right">
                                <x-row-actions :edit="route('admin.members.edit', $m)" :delete="route('admin.members.destroy', $m)" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty :title="t('common.noData')" :hint="t('common.noDataHint')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($members->hasPages())<div class="border-t border-neutral-100 px-4 py-3">{{ $members->links() }}</div>@endif
    </x-card>
</x-layouts.app>

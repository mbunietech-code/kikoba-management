@php $title = t('community.title'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('community.title')" :subtitle="t('community.subtitle')" />

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat :label="t('community.groups')" :value="$groups->where('name', '!=', '__none__')->count()" />
        <x-stat tone="secondary" :label="t('nav.members')" :value="$groups->sum('members')" />
        <x-stat tone="tertiary" :label="t('members.status.active')" :value="$groups->sum('active')" />
    </div>

    <x-card class="mt-4" flush>
        <table class="w-full text-sm">
            <thead><tr class="border-b border-neutral-200 text-left text-[11px] uppercase tracking-wide text-neutral-500">
                <th class="px-4 py-3">{{ t('members.communityGroup') }}</th>
                <th class="px-4 py-3 text-right">{{ t('nav.members') }}</th>
                <th class="px-4 py-3 text-right">{{ t('nav.shares') }}</th>
                <th class="px-4 py-3 text-right">{{ t('nav.savings') }}</th>
                <th></th>
            </tr></thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse ($groups as $g)
                    <tr class="hover:bg-neutral-50">
                        <td class="px-4 py-3">
                            <span class="text-[13.5px] font-medium text-neutral-800">{{ $g['label'] }}</span>
                            <span class="ml-2 text-[11px] text-neutral-400">{{ $g['active'] }}/{{ $g['members'] }} {{ strtolower(t('members.status.active')) }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">{{ num($g['members']) }}</td>
                        <td class="px-4 py-3 text-right">{{ money($g['shares'], true) }}</td>
                        <td class="px-4 py-3 text-right">{{ money($g['savings'], true) }}</td>
                        <td class="px-4 py-3 text-right">
                            <a class="text-[12px] font-medium text-primary-700 hover:underline"
                               href="{{ route('admin.members.index', ['group' => $g['name'] === '__none__' ? '__none__' : $g['name']]) }}">
                                {{ t('common.viewAll') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty :title="t('common.noData')" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </x-card>
</x-layouts.app>

@php
    $editing = $member->exists;
    $title = $editing ? t('common.edit').' — '.$member->full_name : t('members.addMember');
@endphp
<x-layouts.app :title="$title">
    <x-page-header :title="$title" :subtitle="t('members.subtitle')" :back="route('admin.members.index')" :backLabel="t('members.title')" />

    <form method="POST" action="{{ $editing ? route('admin.members.update', $member) : route('admin.members.store') }}" class="grid gap-4 lg:grid-cols-3">
        @csrf
        @if ($editing) @method('PUT') @endif

        <x-card class="lg:col-span-2">
            <x-card-header :title="t('members.tabs.profile')" />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field :label="t('members.fullName')" name="full_name" class="sm:col-span-2">
                    <x-input name="full_name" :value="old('full_name', $member->full_name)" required />
                </x-field>
                <x-field :label="t('common.phone')" name="phone"><x-input name="phone" :value="old('phone', $member->phone)" required /></x-field>
                <x-field :label="t('common.email')" name="email"><x-input type="email" name="email" :value="old('email', $member->email)" /></x-field>
                <x-field :label="t('members.gender')" name="gender">
                    <x-select name="gender">
                        @foreach (['female','male','other'] as $g)
                            <option value="{{ $g }}" @selected(old('gender', $member->gender) === $g)>{{ t("members.$g") }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-field :label="t('members.dob')" name="date_of_birth">
                    <x-input type="date" name="date_of_birth" :value="old('date_of_birth', optional($member->date_of_birth)->toDateString())" />
                </x-field>
                <x-field :label="t('members.address')" name="address">
                    <x-input name="address" :value="old('address', $member->address)" />
                </x-field>
                <x-field :label="t('members.communityGroup')" name="community_group">
                    <x-input name="community_group" :value="old('community_group', $member->community_group)" :placeholder="t('members.communityGroupHint')" />
                </x-field>
            </div>
        </x-card>

        <div class="flex flex-col gap-4">
            <x-card>
                <x-card-header :title="t('members.nextOfKin')" />
                <div class="grid gap-4">
                    <x-field :label="t('common.name')" name="next_of_kin"><x-input name="next_of_kin" :value="old('next_of_kin', $member->next_of_kin)" /></x-field>
                    <x-field :label="t('members.nextOfKinPhone')" name="next_of_kin_phone"><x-input name="next_of_kin_phone" :value="old('next_of_kin_phone', $member->next_of_kin_phone)" /></x-field>
                </div>
            </x-card>
            <x-card>
                <x-card-header :title="t('common.status')" />
                <x-field :label="t('common.status')" name="status">
                    <x-select name="status">
                        @foreach (['pending','active','suspended','inactive','deceased'] as $s)
                            <option value="{{ $s }}" @selected(old('status', $member->status ?? 'pending') === $s)>{{ t("members.status.$s") }}</option>
                        @endforeach
                    </x-select>
                </x-field>
            </x-card>
            <div class="flex gap-2">
                <x-btn variant="outlined" :href="route('admin.members.index')" class="flex-1">{{ t('common.cancel') }}</x-btn>
                <button class="k-btn k-btn-primary flex-1">{{ $editing ? t('common.saveChanges') : t('common.create') }}</button>
            </div>
        </div>
    </form>
</x-layouts.app>

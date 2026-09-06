@php $title = t('common.profile'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('common.profile')" :subtitle="$member->member_number" />
    <div class="grid gap-4 lg:grid-cols-[300px_1fr]">
        <x-card>
            <div class="flex flex-col items-center text-center">
                <x-avatar :name="$member->full_name" :color="$member->avatar_color" size="lg" />
                <p class="mt-3 font-display text-lg font-bold text-neutral-900">{{ $member->full_name }}</p>
                <x-status :status="$member->status" :label="t('members.status.'.$member->status)" />
                <p class="mt-3 text-[12.5px] text-neutral-500">{{ $member->phone }}<br>{{ $member->email }}<br>{{ $member->address }}</p>
            </div>
        </x-card>
        <div class="flex flex-col gap-4">
            <form method="POST" action="{{ route('member.profile.update') }}">
                @csrf @method('PUT')
                <x-card>
                    <x-card-header :title="t('members.tabs.profile')" />
                    <div class="mt-2 grid gap-4 sm:grid-cols-2">
                        <x-field :label="t('common.phone')" name="phone"><x-input name="phone" :value="$member->phone" /></x-field>
                        <x-field :label="t('common.email')" name="email"><x-input type="email" name="email" :value="$member->email" /></x-field>
                        <x-field :label="t('members.address')" name="address" class="sm:col-span-2"><x-input name="address" :value="$member->address" /></x-field>
                        <x-field :label="t('members.nextOfKin')" name="next_of_kin"><x-input name="next_of_kin" :value="$member->next_of_kin" /></x-field>
                        <x-field :label="t('members.nextOfKinPhone')" name="next_of_kin_phone"><x-input name="next_of_kin_phone" :value="$member->next_of_kin_phone" /></x-field>
                    </div>
                    <div class="mt-4 flex justify-end"><button class="k-btn k-btn-primary">{{ t('common.saveChanges') }}</button></div>
                </x-card>
            </form>
            <x-card>
                <x-card-header :title="t('common.details')" />
                <x-kv :items="[
                    ['label' => t('members.memberNumber'), 'value' => $member->member_number],
                    ['label' => t('members.registrationDate'), 'value' => fdate($member->registration_date)],
                    ['label' => t('common.language'), 'value' => view('components.lang-toggle')->render()],
                ]" />
            </x-card>
        </div>
    </div>
</x-layouts.app>

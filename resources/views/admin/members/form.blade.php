@php
    $editing = $member->exists;
    $title = $editing ? t('common.edit').' — '.$member->full_name : t('members.addMember');
    $additionalKin = old('additional_next_of_kin', $member->additional_next_of_kin ?? []);
    $additionalKin = is_array($additionalKin) ? array_values(array_filter($additionalKin, 'is_array')) : [];
@endphp
<x-layouts.app :title="$title">
    <x-page-header :title="$title" :subtitle="t('members.subtitle')" :back="route('admin.members.index')" :backLabel="t('members.title')" />

    <p class="mb-4 text-sm text-neutral-500">{{ t('members.formHint') }}</p>
    <form method="POST" enctype="multipart/form-data" action="{{ $editing ? route('admin.members.update', $member) : route('admin.members.store') }}" class="grid gap-4 lg:grid-cols-3">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="flex flex-col gap-4 lg:col-span-2">
        <x-card>
            <x-card-header :title="t('members.personalDetails')" />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field :label="t('members.fullName')" name="full_name" class="sm:col-span-2">
                    <x-input name="full_name" :value="old('full_name', $member->full_name)" required maxlength="150" autocomplete="name" />
                </x-field>
                <x-field :label="t('members.gender')" name="gender">
                    <x-select name="gender">
                        <option value="">—</option>
                        @foreach (['female','male','other'] as $g)
                            <option value="{{ $g }}" @selected(old('gender', $member->gender) === $g)>{{ t("members.$g") }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-field :label="t('members.dob')" name="date_of_birth">
                    <x-input type="date" name="date_of_birth" :max="now()->toDateString()" :value="old('date_of_birth', optional($member->date_of_birth)->toDateString())" />
                </x-field>
                <x-field :label="t('members.nidaNumber')" name="nida_number" class="sm:col-span-2">
                    <x-input name="nida_number" :value="old('nida_number', $member->nida_number)" inputmode="numeric" maxlength="30" aria-describedby="nida-hint" />
                    <span id="nida-hint" class="mt-1 block text-xs text-neutral-500">{{ t('members.nidaHint') }}</span>
                </x-field>
                <x-field :label="t('members.nationality')" name="nationality"><x-input name="nationality" :value="old('nationality', $member->nationality)" maxlength="100" /></x-field>
                <x-field :label="t('members.maritalStatus')" name="marital_status">
                    <x-select name="marital_status"><option value="">—</option>
                        @foreach (['single','married','divorced','widowed'] as $s)<option value="{{ $s }}" @selected(old('marital_status', $member->marital_status) === $s)>{{ t('members.maritalOptions.'.$s) }}</option>@endforeach
                    </x-select>
                </x-field>
            </div>
        </x-card>
        <x-card>
            <x-card-header :title="t('members.contactDetails')" />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field :label="t('common.phone')" name="phone"><x-input type="tel" name="phone" :value="old('phone', $member->phone)" required maxlength="20" autocomplete="tel" /></x-field>
                <x-field :label="t('members.alternativePhone')" name="alternative_phone"><x-input type="tel" name="alternative_phone" :value="old('alternative_phone', $member->alternative_phone)" maxlength="20" /></x-field>
                <x-field :label="t('common.email')" name="email"><x-input type="email" name="email" :value="old('email', $member->email)" maxlength="191" autocomplete="email" /></x-field>
                <x-field :label="t('members.address')" name="address"><x-input name="address" :value="old('address', $member->address)" maxlength="191" /></x-field>
                @foreach (['region' => 100, 'district' => 100, 'ward' => 100, 'street' => 150, 'house_number' => 50] as $field => $limit)
                    <x-field :label="t('members.'.$field)" :name="$field"><x-input :name="$field" :value="old($field, $member->$field)" :maxlength="$limit" /></x-field>
                @endforeach
                <x-field :label="t('members.communityGroup')" name="community_group">
                    <x-input name="community_group" :value="old('community_group', $member->community_group)" :placeholder="t('members.communityGroupHint')" maxlength="120" />
                </x-field>
            </div>
        </x-card>
        <x-card>
            <x-card-header :title="t('members.employmentDetails')" />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field :label="t('members.occupation')" name="occupation"><x-input name="occupation" :value="old('occupation', $member->occupation)" maxlength="150" /></x-field>
                <x-field :label="t('members.employer')" name="employer"><x-input name="employer" :value="old('employer', $member->employer)" maxlength="150" /></x-field>
            </div>
        </x-card>
        </div>

        <div class="flex flex-col gap-4">
            <x-card>
                <x-card-header :title="t('members.photo')" />
                @if ($member->photo_path)
                    <img src="{{ route('admin.members.photo', $member) }}" alt="{{ $member->full_name }}" class="mb-3 h-24 w-24 rounded-xl object-cover">
                    <label class="mb-3 flex items-center gap-2 text-sm"><input type="checkbox" name="remove_photo" value="1" @checked(old('remove_photo'))> {{ t('members.removePhoto') }}</label>
                @endif
                <x-field :label="t('members.photo')" name="photo"><input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm" aria-describedby="photo-hint"></x-field>
                <p id="photo-hint" class="mt-2 text-xs text-neutral-500">{{ t('members.photoHint') }}</p>
            </x-card>
            <div x-data="{
                kins: @js($additionalKin),
                nextKey: 0,
                init() { this.kins = this.kins.map(row => ({ ...row, _key: this.nextKey++ })); },
                addKin() {
                    this.kins.push({ _key: this.nextKey++, name: '', phone: '', relationship: '', address: '' });
                    this.$nextTick(() => this.$refs.kinList.lastElementChild?.querySelector('input')?.focus());
                }
            }">
            <x-card>
                <x-card-header :title="t('members.nextOfKin')" />
                <input type="hidden" name="kin_editor" value="0" x-bind:value="1">
                <div class="grid gap-4">
                    <x-field :label="t('common.name')" name="next_of_kin"><x-input name="next_of_kin" :value="old('next_of_kin', $member->next_of_kin)" maxlength="150" /></x-field>
                    <x-field :label="t('members.nextOfKinPhone')" name="next_of_kin_phone"><x-input type="tel" name="next_of_kin_phone" :value="old('next_of_kin_phone', $member->next_of_kin_phone)" maxlength="20" /></x-field>
                    <x-field :label="t('members.kinRelationship')" name="next_of_kin_relationship"><x-input name="next_of_kin_relationship" :value="old('next_of_kin_relationship', $member->next_of_kin_relationship)" maxlength="100" /></x-field>
                    <x-field :label="t('members.kinAddress')" name="next_of_kin_address"><x-input name="next_of_kin_address" :value="old('next_of_kin_address', $member->next_of_kin_address)" maxlength="191" /></x-field>
                </div>
                <div x-ref="kinList">
                    <template x-for="(kin, index) in kins" :key="kin._key">
                        <div class="mt-4 grid gap-4 border-t border-neutral-200 pt-4">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-semibold">{{ t('members.nextOfKin') }} <span x-text="index + 2"></span></p>
                                <button type="button" class="k-btn k-btn-ghost k-btn-sm" x-on:click="kins.splice(index, 1)" aria-label="{{ t('members.removeKin') }}"><x-heroicon-o-trash class="h-4 w-4" aria-hidden="true" /></button>
                            </div>
                            <label class="block"><span class="k-label mb-1.5 block">{{ t('common.name') }}</span><input class="k-input" x-model="kin.name" x-bind:name="'additional_next_of_kin[' + index + '][name]'" maxlength="150" required></label>
                            <label class="block"><span class="k-label mb-1.5 block">{{ t('members.nextOfKinPhone') }}</span><input type="tel" class="k-input" x-model="kin.phone" x-bind:name="'additional_next_of_kin[' + index + '][phone]'" maxlength="20"></label>
                            <label class="block"><span class="k-label mb-1.5 block">{{ t('members.kinRelationship') }}</span><input class="k-input" x-model="kin.relationship" x-bind:name="'additional_next_of_kin[' + index + '][relationship]'" maxlength="100"></label>
                            <label class="block"><span class="k-label mb-1.5 block">{{ t('members.kinAddress') }}</span><input class="k-input" x-model="kin.address" x-bind:name="'additional_next_of_kin[' + index + '][address]'" maxlength="191"></label>
                        </div>
                    </template>
                </div>
                @foreach ($errors->getMessages() as $field => $messages)
                    @if (str_starts_with($field, 'additional_next_of_kin'))
                        <p class="mt-2 text-xs text-red-600" role="alert">{{ $messages[0] }}</p>
                    @endif
                @endforeach
                <button type="button" class="k-btn k-btn-outlined mt-4 w-full" x-on:click="addKin()" x-bind:disabled="kins.length >= 20">
                    <x-heroicon-o-plus class="h-4 w-4" aria-hidden="true" /> {{ t('members.addKin') }}
                </button>
            </x-card>
            </div>
            <x-card>
                <x-card-header :title="t('members.membershipDetails')" />
                <x-field :label="t('members.registrationDate')" name="registration_date" class="mb-4"><x-input type="date" name="registration_date" :max="now()->toDateString()" :value="old('registration_date', $member->registration_date?->toDateString() ?? now()->toDateString())" /></x-field>
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

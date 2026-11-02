@php $title = t('account.title'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="t('account.title')" :subtitle="$user->email.' · '.t('users.roles.'.($user->primaryRole() ?? 'member'))" />
    <div class="grid max-w-3xl gap-4">
        <x-card>
            <x-card-header :title="t('account.details')" />
            <form method="POST" action="{{ route('admin.account.update') }}" class="grid gap-4">
                @csrf @method('PUT')
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field :label="t('common.name')" name="name"><x-input name="name" required maxlength="120" :value="old('name', $user->name)" /></x-field>
                    <x-field :label="t('common.phone')" name="phone"><x-input name="phone" maxlength="20" :value="old('phone', $user->phone)" /></x-field>
                </div>
                <div><button class="k-btn k-btn-primary">{{ t('common.saveChanges') }}</button></div>
            </form>
        </x-card>
        <x-card>
            <x-card-header :title="t('account.changePassword')" />
            @include('partials.password-form')
        </x-card>
    </div>
</x-layouts.app>

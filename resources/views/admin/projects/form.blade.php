@php $editing = $project->exists; $title = $editing ? t('common.edit').' — '.$project->name : t('projects.addProject'); @endphp
<x-layouts.app :title="$title">
    <x-page-header :title="$title" :subtitle="t('projects.subtitle')" :back="route('admin.projects.index')" :backLabel="t('projects.title')" />
    <form method="POST" action="{{ $editing ? route('admin.projects.update', $project) : route('admin.projects.store') }}" class="max-w-2xl">
        @csrf @if ($editing) @method('PUT') @endif
        <x-card>
            <div class="grid gap-4">
                <x-field :label="t('projects.projectName')" name="name"><x-input name="name" :value="old('name', $project->name)" /></x-field>
                <x-field :label="t('common.description')" name="description"><x-textarea name="description">{{ old('description', $project->description) }}</x-textarea></x-field>
                <div class="grid grid-cols-2 gap-4">
                    <x-field :label="t('projects.capitalRequired')" name="capital_required"><x-input type="number" name="capital_required" :value="old('capital_required', $project->capital_required)" /></x-field>
                    <x-field :label="t('projects.expectedProfit')" name="expected_profit"><x-input type="number" name="expected_profit" :value="old('expected_profit', $project->expected_profit)" /></x-field>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <x-field :label="t('projects.startDate')" name="start_date"><x-input type="date" name="start_date" :value="old('start_date', optional($project->start_date)->toDateString())" /></x-field>
                    <x-field :label="t('projects.endDate')" name="end_date"><x-input type="date" name="end_date" :value="old('end_date', optional($project->end_date)->toDateString())" /></x-field>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <x-field :label="t('common.type')" name="type">
                        <x-select name="type">@foreach (['monthly','three_months','long_term','custom'] as $ty)<option value="{{ $ty }}" @selected(old('type', $project->type)===$ty)>{{ t("projects.type.$ty") }}</option>@endforeach</x-select>
                    </x-field>
                    <x-field :label="t('common.status')" name="status">
                        <x-select name="status">@foreach (['planned','active','completed','cancelled'] as $s)<option value="{{ $s }}" @selected(old('status', $project->status ?? 'planned')===$s)>{{ t("projects.status.$s") }}</option>@endforeach</x-select>
                    </x-field>
                </div>
                @if ($editing)<x-field :label="t('projects.actualProfit')" name="actual_profit"><x-input type="number" name="actual_profit" :value="old('actual_profit', $project->actual_profit)" /></x-field>@endif
                <x-field :label="t('projects.manager')" name="manager"><x-input name="manager" :value="old('manager', $project->manager)" /></x-field>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <x-btn variant="outlined" :href="route('admin.projects.index')">{{ t('common.cancel') }}</x-btn>
                <button class="k-btn k-btn-primary">{{ t('common.save') }}</button>
            </div>
        </x-card>
    </form>
</x-layouts.app>

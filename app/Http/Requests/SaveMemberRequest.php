<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $member = $this->route('member');

        return $this->user()?->can($member ? 'members.update' : 'members.create')
            && (! $member || $member->organization_id === $this->user()->organization_id);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:20'],
            'alternative_phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:191'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'nida_number' => ['nullable', 'string', 'regex:/^[0-9]{20}$/',
                Rule::unique('members', 'nida_number')
                    ->where('organization_id', $this->user()->organization_id)
                    ->ignore($this->route('member'))],
            'nationality' => ['nullable', 'string', 'max:100'],
            'marital_status' => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed'])],
            'address' => ['nullable', 'string', 'max:191'],
            'region' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'ward' => ['nullable', 'string', 'max:100'],
            'street' => ['nullable', 'string', 'max:150'],
            'house_number' => ['nullable', 'string', 'max:50'],
            'occupation' => ['nullable', 'string', 'max:150'],
            'employer' => ['nullable', 'string', 'max:150'],
            'community_group' => ['nullable', 'string', 'max:120'],
            'next_of_kin' => ['nullable', 'string', 'max:150', 'required_with:next_of_kin_phone,next_of_kin_relationship,next_of_kin_address'],
            'next_of_kin_phone' => ['nullable', 'string', 'max:20'],
            'next_of_kin_relationship' => ['nullable', 'string', 'max:100'],
            'next_of_kin_address' => ['nullable', 'string', 'max:191'],
            'additional_next_of_kin' => ['sometimes', 'array', 'max:20'],
            'additional_next_of_kin.*' => ['array:name,phone,relationship,address'],
            'additional_next_of_kin.*.name' => ['required', 'string', 'max:150'],
            'additional_next_of_kin.*.phone' => ['nullable', 'string', 'max:20'],
            'additional_next_of_kin.*.relationship' => ['nullable', 'string', 'max:100'],
            'additional_next_of_kin.*.address' => ['nullable', 'string', 'max:191'],
            'registration_date' => ['nullable', 'date', 'before_or_equal:today', ...($this->filled('date_of_birth') ? ['after_or_equal:date_of_birth'] : [])],
            'status' => ['required', Rule::in(['pending', 'active', 'suspended', 'inactive', 'deceased'])],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->boolean('kin_editor') && ! $this->has('additional_next_of_kin')) {
            $this->merge(['additional_next_of_kin' => []]);
        }
        if (is_array($this->input('additional_next_of_kin'))) {
            $rows = array_filter($this->input('additional_next_of_kin'), function ($row): bool {
                return ! is_array($row) || count(array_filter($row, fn ($value) => $value !== null && $value !== '')) > 0;
            });
            $this->merge(['additional_next_of_kin' => array_values($rows)]);
        }

        if (is_string($this->input('nida_number'))) {
            $this->merge(['nida_number' => preg_replace('/[\s-]+/', '', $this->input('nida_number')) ?: null]);
        }
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nida_number.regex' => t('members.nidaInvalid'),
            'nida_number.unique' => t('members.nidaDuplicate'),
            'additional_next_of_kin.*.name.required' => t('members.kinNameRequired'),
        ];
    }
}

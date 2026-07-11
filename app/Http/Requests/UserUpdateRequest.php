<?php

namespace App\Http\Requests;

use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->id();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->route('user')->id),
            ],
            'password' => ['nullable', Password::min(8)],
            'role' => [
                'required', 'string',
                Rule::exists('roles', 'name')->where('team_id', $tenantId)->where('guard_name', 'web'),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Ky email eshte tashme ne perdorim.',
            'role.exists' => 'Roli i zgjedhur nuk ekziston.',
        ];
    }
}

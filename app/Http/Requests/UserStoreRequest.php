<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        $email = strtolower(trim((string) $this->input('email')));
        $tenantId = app(TenantContext::class)->id();
        $existingAccount = User::withoutGlobalScopes()
            ->withTrashed()
            ->where('email', $email)
            ->exists();

        $passwordRules = $existingAccount
            ? ['nullable', Password::min(8)]
            : ['required', Password::min(8)];

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($tenantId) {
                    $alreadyActive = DB::table('users')
                        ->join('tenant_user', 'tenant_user.user_id', '=', 'users.id')
                        ->where('users.email', strtolower(trim((string) $value)))
                        ->where('tenant_user.tenant_id', $tenantId)
                        ->where('tenant_user.is_active', true)
                        ->exists();

                    if ($alreadyActive) {
                        $fail('Ky email eshte tashme aktiv ne kete hotel.');
                    }
                },
            ],
            'password' => $passwordRules,
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

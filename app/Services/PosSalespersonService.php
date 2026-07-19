<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PosSalespersonService
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function settings(): array
    {
        return [
            'service_mode' => Setting::get('pos.service_mode', 'hybrid'),
            'opening_view' => Setting::get('pos.opening_view', 'products'),
            'salesperson_enabled' => (bool) Setting::get('pos.salesperson_enabled', true),
            'salesperson_required' => (bool) Setting::get('pos.salesperson_required', true),
        ];
    }

    public function staff(): Collection
    {
        $tenantId = $this->tenantContext->id();
        if (! $tenantId) {
            return collect();
        }

        return DB::table('tenant_user')
            ->join('users', 'users.id', '=', 'tenant_user.user_id')
            ->where('tenant_user.tenant_id', $tenantId)
            ->where('tenant_user.is_active', true)
            ->whereNull('users.deleted_at')
            ->orderBy('users.name')
            ->get([
                'users.id', 'users.name', 'users.email',
                'tenant_user.pos_salesperson_enabled', 'tenant_user.pos_pin_hash',
            ])
            ->reject(fn ($user) => User::isSystemEmail($user->email))
            ->map(fn ($user) => [
                'id' => (int) $user->id,
                'name' => $user->name,
                'enabled' => (bool) $user->pos_salesperson_enabled,
                'has_pin' => filled($user->pos_pin_hash),
            ])->values();
    }

    public function current(Request $request): User
    {
        if (! $this->settings()['salesperson_enabled']) {
            return $request->user();
        }

        $key = $this->sessionKey();
        $selected = $request->session()->get($key);
        $allowedIds = $this->staff()->where('enabled', true)->pluck('id');

        if ($selected && $allowedIds->contains((int) $selected)) {
            return User::query()->findOrFail($selected);
        }

        $fallback = $allowedIds->contains((int) $request->user()->id)
            ? $request->user()
            : User::query()->find($allowedIds->first());

        if (! $fallback) {
            throw ValidationException::withMessages([
                'salesperson' => 'Nuk ka salesperson aktiv për këtë hotel.',
            ]);
        }

        $request->session()->put($key, $fallback->id);

        return $fallback;
    }

    public function switch(Request $request, int $userId, string $pin): User
    {
        $row = $this->membership($userId);
        if (! $row || ! $row->pos_salesperson_enabled || ! $row->pos_pin_hash || ! Hash::check($pin, $row->pos_pin_hash)) {
            throw ValidationException::withMessages(['pin' => 'PIN-i nuk është i saktë.']);
        }

        $request->session()->put($this->sessionKey(), $userId);
        $user = User::query()->findOrFail($userId);
        AuditLog::record('pos.salesperson.switched', $user, [
            'from_user_id' => $request->user()->id,
            'salesperson_id' => $userId,
        ]);

        return $user;
    }

    public function verifyPin(int $userId, string $pin): User
    {
        $row = $this->membership($userId);
        if (! $row || ! $row->pos_salesperson_enabled || ! $row->pos_pin_hash || ! Hash::check($pin, $row->pos_pin_hash)) {
            throw ValidationException::withMessages(['pin' => 'PIN-i nuk është i saktë.']);
        }

        return User::query()->findOrFail($userId);
    }

    private function membership(int $userId): ?object
    {
        return DB::table('tenant_user')
            ->where('tenant_id', $this->tenantContext->id())
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->first();
    }

    private function sessionKey(): string
    {
        return 'pos.salesperson.'.($this->tenantContext->id() ?? 'none');
    }
}

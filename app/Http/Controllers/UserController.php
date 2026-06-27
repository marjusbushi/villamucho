<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::select('id', 'name', 'email', 'created_at', 'deleted_at')
            ->withTrashed()
            ->with('roles:id,name')
            ->orderBy('name')
            ->paginate(15);

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => Role::pluck('name'),
        ]);
    }

    public function store(UserStoreRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => bcrypt($request->validated('password')),
        ]);

        $user->assignRole($request->validated('role'));

        AuditLog::record('user.create', $user, ['role' => $request->validated('role')]);

        return back()->with('success', 'Perdoruesi u krijua me sukses.');
    }

    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        $data = [
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
        ];

        if ($request->validated('password')) {
            $data['password'] = bcrypt($request->validated('password'));
        }

        $user->update($data);
        $user->syncRoles([$request->validated('role')]);

        AuditLog::record('user.update', $user, ['role' => $request->validated('role')]);

        return back()->with('success', 'Perdoruesi u perditesua me sukses.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Nuk mund te fshish veten.');
        }

        $user->delete();

        AuditLog::record('user.delete', $user);

        return back()->with('success', 'Perdoruesi u deaktivizua.');
    }

    public function restore(int $id): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();

        return back()->with('success', 'Perdoruesi u riaktivizua.');
    }
}

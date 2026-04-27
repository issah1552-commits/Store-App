<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\UserStoreRequest;
use App\Http\Requests\Users\UserUpdateRequest;
use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', User::class);

        return Inertia::render('users/index', [
            'users' => User::query()
                ->with(['role:id,name,display_name', 'defaultLocation:id,name,code'])
                ->orderBy('name')
                ->paginate(12)
                ->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('users/form', [
            'mode' => 'create',
            'roles' => Role::query()->orderBy('display_name')->get(['id', 'name', 'display_name']),
            'locations' => Location::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'type']),
        ]);
    }

    public function store(UserStoreRequest $request, AuditLogService $auditLogService): RedirectResponse
    {
        $this->authorize('create', User::class);

        $user = DB::transaction(function () use ($request, $auditLogService) {
            $user = User::create([
                'name' => $request->string('name')->toString(),
                'username' => $request->string('username')->toString(),
                'email' => $request->string('email')->toString(),
                'phone' => $request->input('phone'),
                'role_id' => $request->integer('role_id'),
                'default_location_id' => $request->integer('default_location_id') ?: null,
                'password' => Hash::make($request->string('password')->toString()),
                'is_active' => $request->boolean('is_active', true),
                'status' => $request->boolean('is_active', true) ? \App\Enums\UserStatus::Active : \App\Enums\UserStatus::Inactive,
                'email_verified_at' => now(),
            ]);

            $locationIds = collect($request->input('location_ids', []))
                ->push($request->integer('default_location_id'))
                ->filter()
                ->unique()
                ->mapWithKeys(fn ($locationId) => [$locationId => ['is_primary' => (int) $locationId === (int) $request->integer('default_location_id')]])
                ->all();

            $user->assignedLocations()->sync($locationIds);

            $auditLogService->record(
                $request->user(),
                'user',
                'user.created',
                'Created user '.$user->email,
                $user,
                $user->defaultLocation,
                ['email' => $user->email],
                $request,
            );

            return $user;
        });

        return redirect()->route('users.edit', $user)->with('success', 'User created successfully.');
    }

    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        $user->load(['role:id,name,display_name', 'defaultLocation:id,name,code', 'assignedLocations:id,name,code,type']);

        return Inertia::render('users/form', [
            'mode' => 'edit',
            'userRecord' => $user,
            'roles' => Role::query()->orderBy('display_name')->get(['id', 'name', 'display_name']),
            'locations' => Location::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'type']),
        ]);
    }

    public function update(UserUpdateRequest $request, User $user, AuditLogService $auditLogService): RedirectResponse
    {
        $this->authorize('update', $user);

        DB::transaction(function () use ($request, $user, $auditLogService) {
            $payload = [
                'name' => $request->string('name')->toString(),
                'username' => $request->string('username')->toString(),
                'email' => $request->string('email')->toString(),
                'phone' => $request->input('phone'),
                'role_id' => $request->integer('role_id'),
                'default_location_id' => $request->integer('default_location_id') ?: null,
                'is_active' => $request->boolean('is_active', true),
                'status' => $request->boolean('is_active', true) ? \App\Enums\UserStatus::Active : \App\Enums\UserStatus::Inactive,
            ];

            if ($request->filled('password')) {
                $payload['password'] = Hash::make($request->string('password')->toString());
            }

            $user->update($payload);

            $locationIds = collect($request->input('location_ids', []))
                ->push($request->integer('default_location_id'))
                ->filter()
                ->unique()
                ->mapWithKeys(fn ($locationId) => [$locationId => ['is_primary' => (int) $locationId === (int) $request->integer('default_location_id')]])
                ->all();

            $user->assignedLocations()->sync($locationIds);

            $auditLogService->record(
                $request->user(),
                'user',
                'user.updated',
                'Updated user '.$user->email,
                $user,
                $user->defaultLocation,
                ['email' => $user->email],
                $request,
            );
        });

        return redirect()->route('users.edit', $user)->with('success', 'User updated successfully.');
    }

    public function toggleActive(User $user, Request $request, AuditLogService $auditLogService): RedirectResponse
    {
        $this->authorize('deactivate', $user);

        $user->update([
            'is_active' => ! $user->is_active,
            'status' => ! $user->is_active ? \App\Enums\UserStatus::Active : \App\Enums\UserStatus::Inactive,
        ]);

        $auditLogService->record(
            $request->user(),
            'user',
            'user.toggled_active',
            'Toggled active status for '.$user->email,
            $user,
            $user->defaultLocation,
            ['email' => $user->email, 'is_active' => $user->is_active],
            $request,
        );

        return back()->with('success', 'User status updated.');
    }
}

<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformUser;
use App\Services\Platform\PlatformUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(PlatformUserService $service): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.users.manage');

        return view('platform.users.index', [
            'users' => $service->paginate(),
            'roles' => config('platform.roles'),
        ]);
    }

    public function create(): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.users.manage');

        return view('platform.users.create', [
            'roles' => config('platform.roles'),
        ]);
    }

    public function store(Request $request, PlatformUserService $service): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.users.manage');
        $service->assertCanManage(auth('platform')->user());

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:platform_users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(array_keys(config('platform.roles')))],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $service->create($validated, auth('platform')->user());

        return redirect()->route('platform.users.index')
            ->with('status', __('Platform user created.'));
    }

    public function edit(PlatformUser $user): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.users.manage');
        $service = app(PlatformUserService::class);
        $service->assertCanManage(auth('platform')->user(), $user);

        return view('platform.users.edit', [
            'user' => $user,
            'roles' => config('platform.roles'),
        ]);
    }

    public function update(Request $request, PlatformUser $user, PlatformUserService $service): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.users.manage');
        $service->assertCanManage(auth('platform')->user(), $user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('platform_users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', Rule::in(array_keys(config('platform.roles')))],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $service->update($user, $validated, auth('platform')->user());

        return redirect()->route('platform.users.index')
            ->with('status', __('Platform user updated.'));
    }
}

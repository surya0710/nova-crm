<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApiTokenRequest;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApiTokenController extends Controller
{
    public function index(Request $request, TenantContext $tenant): View
    {
        abort_unless($request->user()->hasPermission('api.tokens'), 403);

        return view('api-tokens.index', [
            'organization' => $tenant->get(),
            'tokens' => $request->user()->tokens()->latest()->get(),
            'plainTextToken' => session('api_token_plain'),
        ]);
    }

    public function store(StoreApiTokenRequest $request): RedirectResponse
    {
        $token = $request->user()->createToken(
            $request->validated('name'),
            $request->validated('abilities', ['*'])
        );

        return redirect()
            ->route('api-tokens.index')
            ->with('status', 'api-token-created')
            ->with('api_token_plain', $token->plainTextToken);
    }

    public function destroy(Request $request, int $token): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('api.tokens'), 403);

        $request->user()->tokens()->where('id', $token)->delete();

        return redirect()
            ->route('api-tokens.index')
            ->with('status', 'api-token-deleted');
    }
}

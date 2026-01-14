<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Controllers;

use App\Domain\Integrations\Actions\UpsertIntegrationCredentialAction;
use App\Domain\Integrations\Requests\StoreIntegrationCredentialRequest;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

final class IntegrationCredentialController
{
    public function create(): Response
    {
        $user = Auth::user();
        abort_unless($user, 401);

        return Inertia::render('Integrations/Credentials', [
            'provider' => 'gmail',
            'redirectHint' => config('app.url').'/integrations/gmail/callback',
        ]);
    }

    public function store(
        StoreIntegrationCredentialRequest $request,
        UpsertIntegrationCredentialAction $upsertIntegrationCredentialAction,
    ): RedirectResponse {
        $user = $request->user();

        $upsertIntegrationCredentialAction->handle($user, 'google', $request->validated());

        return redirect()
            ->route('integrations.index')
            ->with('success', 'Credenciais do Gmail salvas com sucesso.');
    }
}

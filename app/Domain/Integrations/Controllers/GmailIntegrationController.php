<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Controllers;

use App\Domain\Integrations\Actions\UpsertIntegrationAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

final class GmailIntegrationController
{
    public function __construct(
        private readonly UpsertIntegrationAction $upsertIntegrationAction,
    ) {}

    public function redirect(): SymfonyRedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 401);

        /** @var GoogleProvider $driver */
        $driver = Socialite::driver('google')
            ->scopes([
                'https://www.googleapis.com/auth/gmail.readonly',
                'https://www.googleapis.com/auth/gmail.modify',
                'https://www.googleapis.com/auth/gmail.send',
                'https://www.googleapis.com/auth/userinfo.email',
                'https://www.googleapis.com/auth/userinfo.profile',
            ])
            ->with(['access_type' => 'offline', 'prompt' => 'consent']);

        return $driver->redirect();
    }

    public function callback(): RedirectResponse
    {
        // Se o usuário não estiver logado, assume que é um login/registro via Google
        if (! Auth::check()) {
            return app(\App\Http\Controllers\User\OauthController::class)->callback('google');
        }

        $user = Auth::user();
        abort_unless($user, 401);

        // Use stateless() to avoid InvalidStateException when state is mismatched/missing. 
        // We rely on the callback code validation itself.
        $socialiteUser = Socialite::driver('google')->stateless()->user();

        $metadata = [
            'google_id' => $socialiteUser->getId(),
            'email' => $socialiteUser->getEmail(),
            'name' => $socialiteUser->getName(),
            'avatar' => $socialiteUser->getAvatar(),
            'token' => $socialiteUser->token,
            'refresh_token' => $socialiteUser->refreshToken ?? null,
            'expires_in' => $socialiteUser->expiresIn ?? null,
        ];

        $this->upsertIntegrationAction->handle(
            user: $user,
            provider: 'gmail',
            status: 'connected',
            metadata: $metadata,
            connectedAt: Carbon::now(),
            revokedAt: null,
        );

        return Redirect::route('integrations.index')->with(
            'success',
            'Gmail conectado com sucesso.',
        );
    }

    public function disconnect(): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 401);

        $this->upsertIntegrationAction->handle(
            user: $user,
            provider: 'gmail',
            status: 'disconnected',
            metadata: [],
            connectedAt: null,
            revokedAt: Carbon::now(),
        );

        return Redirect::route('integrations.index')->with(
            'success',
            'Gmail desconectado.',
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Actions;

use App\Domain\Integrations\Models\IntegrationCredential;
use App\Models\User;

final class UpsertIntegrationCredentialAction
{
    public function handle(User $user, string $provider, array $data): IntegrationCredential
    {
        return IntegrationCredential::updateOrCreate(
            ['user_id' => $user->id, 'provider' => $provider],
            [
                'client_id' => $data['client_id'],
                'client_secret' => $data['client_secret'],
                'redirect_uri' => $data['redirect_uri'],
            ],
        );
    }
}

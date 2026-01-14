<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Actions;

use App\Domain\Integrations\Models\IntegrationCredential;
use App\Models\User;
use Illuminate\Support\Arr;
use RuntimeException;

final class ResolveIntegrationCredentialsAction
{
    public function handle(User $user, string $provider): array
    {
        $record = IntegrationCredential::query()
            ->where('user_id', $user->id)
            ->where('provider', $provider)
            ->first();

        if ($record) {
            return [
                'client_id' => $record->client_id,
                'client_secret' => $record->client_secret,
                'redirect' => $record->redirect_uri,
            ];
        }

        // fallback para credenciais globais (útil em sandbox)
        $env = config("services.{$provider}");
        if ($env && Arr::get($env, 'client_id') && Arr::get($env, 'client_secret')) {
            return [
                'client_id' => Arr::get($env, 'client_id'),
                'client_secret' => Arr::get($env, 'client_secret'),
                'redirect' => Arr::get($env, 'redirect'),
            ];
        }

        throw new RuntimeException("Credenciais de {$provider} não configuradas para este usuário.");
    }
}

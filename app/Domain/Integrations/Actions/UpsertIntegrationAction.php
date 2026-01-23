<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Actions;

use App\Domain\Integrations\Models\Integration;
use App\Models\User;
use Carbon\CarbonInterface;

/**
 * Action para criar ou atualizar uma integração
 * 
 * Usado quando usuário conecta/reconecta Gmail, Google Sheets, etc
 * Multi-tenancy: Integração vinculada ao team atual do usuário
 */
final class UpsertIntegrationAction
{
    /**
     * Cria ou atualiza integração do usuário
     * 
     * @param User $user Usuário autenticado
     * @param string $provider Nome do provider (gmail, sheets, etc)
     * @param string $status Status da integração (connected, disconnected, etc)
     * @param array $metadata Metadados (token, refresh_token, email, etc)
     * @param CarbonInterface|null $connectedAt Timestamp de conexão
     * @param CarbonInterface|null $revokedAt Timestamp de revogação
     * @return Integration Integração criada/atualizada
     */
    public function handle(
        User $user,
        string $provider,
        string $status,
        array $metadata = [],
        ?CarbonInterface $connectedAt = null,
        ?CarbonInterface $revokedAt = null,
    ): Integration {
        return Integration::updateOrCreate(
            [
                'user_id' => $user->id,
                'team_id' => $user->currentTeam->id, // OBRIGATÓRIO - Multi-tenancy
                'provider' => $provider,
            ],
            [
                'status' => $status,
                'metadata' => $metadata,
                'connected_at' => $connectedAt,
                'revoked_at' => $revokedAt,
            ],
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Actions;

use App\Domain\Integrations\Models\Integration;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Action para listar integrações do usuário
 * 
 * Multi-tenancy: Filtra por team atual do usuário
 * Retorna Collection indexada por provider (gmail, sheets, etc)
 */
final class ListUserIntegrationsAction
{
    /**
     * Lista todas as integrações do usuário no team atual
     * 
     * @param User $user Usuário autenticado
     * @return Collection Integrações indexadas por provider
     */
    public function handle(User $user): Collection
    {
        return Integration::query()
            ->where('user_id', $user->id)
            ->where('team_id', $user->currentTeam->id) // OBRIGATÓRIO - Multi-tenancy
            ->get()
            ->keyBy('provider');
    }
}

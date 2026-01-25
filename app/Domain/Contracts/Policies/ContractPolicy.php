<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Policies;

use App\Domain\Contracts\Models\Contract;
use App\Models\User;

/**
 * Policy para autorização de ações em contratos
 */
class ContractPolicy
{
    /**
     * Verifica se usuário pode visualizar contratos
     */
    public function viewAny(User $user): bool
    {
        return $user->currentTeam !== null;
    }

    /**
     * Verifica se usuário pode visualizar um contrato específico
     */
    public function view(User $user, Contract $contract): bool
    {
        return $user->currentTeam->id === $contract->team_id;
    }

    /**
     * Verifica se usuário pode criar contratos
     */
    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }

    /**
     * Verifica se usuário pode atualizar contrato
     */
    public function update(User $user, Contract $contract): bool
    {
        return $user->currentTeam->id === $contract->team_id;
    }

    /**
     * Verifica se usuário pode excluir contrato
     */
    public function delete(User $user, Contract $contract): bool
    {
        return $user->currentTeam->id === $contract->team_id;
    }
}

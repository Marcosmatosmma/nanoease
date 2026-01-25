<?php

declare(strict_types=1);

namespace App\Domain\Tasks\Actions;

use App\Domain\Tasks\Models\Board;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Action para listar boards do usuário no team atual
 * 
 * Retorna boards com listas e tarefas carregadas
 * Ordenado por position
 */
class ListUserBoardsAction
{
    /**
     * Lista todos os boards do usuário no team atual
     * Com listas e tarefas eager loaded
     * 
     * @param User $user Usuário autenticado
     * @return Collection Boards com listas e tarefas
     */
    public function handle(User $user): Collection
    {
        return Board::query()
            ->with([
                'lists' => function ($query) {
                    $query->orderBy('position');
                },
                'lists.tasks' => function ($query) {
                    $query->with(['assignedUser', 'labels', 'boardList'])
                        ->notArchived()
                        ->orderBy('position');
                },
            ])
            ->where('user_id', $user->id)
            ->where('team_id', $user->currentTeam->id)
            ->orderBy('position')
            ->get();
    }
}

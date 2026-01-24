<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Domain\Tasks\Models\Board;
use App\Domain\Tasks\Models\BoardList;
use App\Models\User;

class BoardSeeder extends Seeder
{
    /**
     * Cria board padrão com listas iniciais para cada usuário
     * 
     * Listas padrão:
     * - A Fazer
     * - Em Andamento
     * - Concluído
     */
    public function run(): void
    {
        // Busca todos os usuários
        $users = User::with('currentTeam')->get();

        foreach ($users as $user) {
            if (!$user->currentTeam) continue;

            // Cria board padrão
            $board = Board::create([
                'user_id' => $user->id,
                'team_id' => $user->currentTeam->id,
                'name' => 'Tarefas',
                'description' => 'Quadro principal de tarefas',
                'color' => '#3b82f6',
                'position' => 0,
                'is_default' => true,
            ]);

            // Cria listas padrão
            $lists = [
                [
                    'name' => 'A Fazer',
                    'color' => '#6b7280',
                    'position' => 0,
                ],
                [
                    'name' => 'Em Andamento',
                    'color' => '#3b82f6',
                    'position' => 1,
                ],
                [
                    'name' => 'Concluído',
                    'color' => '#10b981',
                    'position' => 2,
                ],
            ];

            foreach ($lists as $list) {
                BoardList::create([
                    'board_id' => $board->id,
                    ...$list,
                ]);
            }

            $this->command->info("✅ Board criado para: {$user->name}");
        }

        $this->command->info('✅ Boards e listas padrão criados com sucesso!');
    }
}

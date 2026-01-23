<?php

declare(strict_types=1);

namespace App\Domain\Automations\Actions;

use App\Domain\Automations\Models\Automation;
use App\Domain\Automations\Models\MassEmailSend;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Lista todas as automações do usuário no team atual
 * 
 * Inclui:
 * - Automações de email recebido (email_received)
 * - Envios em massa (mass_email_send)
 * 
 * Multi-tenancy: Filtra por team atual do usuário
 */
final class ListUserAutomationsAction
{
    /**
     * Lista automações do usuário no team atual
     * 
     * @param User $user Usuário autenticado
     * @return Collection Automações combinadas e ordenadas por data
     */
    public function handle(User $user): Collection
    {
        // Busca automações de email recebido do team atual
        $emailAutomations = Automation::query()
            ->with([
                'actions' => function ($query) {
                    $query->orderBy('position');
                },
                'event',
            ])
            ->where('user_id', $user->id)
            ->where('team_id', $user->currentTeam->id) // OBRIGATÓRIO - Multi-tenancy
            ->get()
            ->map(function ($automation) {
                $action = $automation->actions->first();
                
                return [
                    'id' => $automation->id,
                    'type' => 'email_automation',
                    'event' => $automation->event,
                    'rule' => $automation->rule_text,
                    'status' => $automation->status,
                    'action_type' => $action?->type,
                    'updated_at' => $automation->updated_at,
                    'created_at' => $automation->created_at,
                ];
            });

        // Busca envios em massa do team atual
        $massEmailSends = MassEmailSend::query()
            ->with('automationEvent')
            ->where('user_id', $user->id)
            ->where('team_id', $user->currentTeam->id) // OBRIGATÓRIO - Multi-tenancy
            ->get()
            ->map(function ($massEmail) {
                return [
                    'id' => $massEmail->id,
                    'type' => 'mass_email_send',
                    'event' => $massEmail->automationEvent,
                    'rule' => $massEmail->name,
                    'status' => $massEmail->status,
                    'action_type' => 'envio_massa',
                    'updated_at' => $massEmail->updated_at,
                    'created_at' => $massEmail->created_at,
                    // Informações específicas de envio em massa
                    'stats' => [
                        'total' => $massEmail->total_recipients,
                        'sent' => $massEmail->sent_count,
                        'failed' => $massEmail->failed_count,
                    ],
                    'send_type' => $massEmail->send_type,
                    'scheduled_at' => $massEmail->scheduled_at,
                ];
            });

        // Combina e ordena por data de atualização
        return $emailAutomations
            ->concat($massEmailSends)
            ->sortByDesc('updated_at')
            ->values();
    }
}

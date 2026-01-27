<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $user = auth()->user();
        $teamId = $user->currentTeam->id;

        // Query base para contratos do time
        $query = \App\Domain\Contracts\Models\Contract::where('team_id', $teamId);

        // Totais Gerais (Ativos)
        $activeContracts = (clone $query)->where('status', 'ativo')->get();
        $totalActive = $activeContracts->count();

        // Por Papel (Ativos)
        $byRole = [
            'contratante' => [
                'count' => $activeContracts->where('my_role', 'contratante')->count(),
                'value' => $activeContracts->where('my_role', 'contratante')->sum('amount'),
            ],
            'contratado' => [
                'count' => $activeContracts->where('my_role', 'contratado')->count(),
                'value' => $activeContracts->where('my_role', 'contratado')->sum('amount'),
            ],
        ];

        // Alertas: Vencendo em 30 dias
        $expiringSoon = (clone $query)
            ->where('status', 'ativo')
            ->where('end_date', '>=', now())
            ->where('end_date', '<=', now()->addDays(30))
            ->count();

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_active' => $totalActive,

                'by_role' => $byRole,
                'expiring_soon' => $expiringSoon,
            ]
        ]);
    }
}

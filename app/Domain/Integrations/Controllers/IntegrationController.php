<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Controllers;

use App\Domain\Integrations\Actions\ListUserIntegrationsAction;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class IntegrationController extends Controller
{
    public function index(ListUserIntegrationsAction $listUserIntegrationsAction): Response
    {
        $user = Auth::user();
        $userIntegrations = $listUserIntegrationsAction->handle($user);

        $providers = collect([
            [
                'key' => 'gmail',
                'name' => 'Gmail',
                'description' => 'Acesse seus e-mails e crie automações',
                'comingSoon' => false,
            ],
            [
                'key' => 'google_agenda',
                'name' => 'Google Agenda',
                'description' => 'Sincronize eventos e lembretes',
                'comingSoon' => true,
            ],
            [
                'key' => 'banking',
                'name' => 'Conta bancária',
                'description' => 'Acompanhe transações e pagamentos',
                'comingSoon' => true,
            ],
            [
                'key' => 'gdrive',
                'name' => 'Google Drive',
                'description' => 'Gerencie documentos automaticamente',
                'comingSoon' => true,
            ],
        ])->map(function (array $provider) use ($userIntegrations) {
            $record = $userIntegrations->get($provider['key']);

            return [
                'key' => $provider['key'],
                'name' => $provider['name'],
                'description' => $provider['description'],
                'comingSoon' => $provider['comingSoon'],
                'status' => $record?->status ?? ($provider['comingSoon'] ? 'coming_soon' : 'disconnected'),
                'connectedAt' => $record?->connected_at,
                'needsCredentials' => false,
            ];
        })->values();

        $connectedCount = $providers
            ->filter(fn ($provider) => $provider['status'] === 'connected')
            ->count();

        return Inertia::render('Integrations/Index', [
            'providers' => $providers,
            'stats' => [
                'connected' => $connectedCount,
                'total' => $providers->count(),
            ],
        ]);
    }
}

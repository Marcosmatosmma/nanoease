<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Actions;

use App\Domain\Integrations\Models\Integration;
use App\Models\User;
use Carbon\CarbonInterface;

final class UpsertIntegrationAction
{
    public function handle(
        User $user,
        string $provider,
        string $status,
        array $metadata = [],
        ?CarbonInterface $connectedAt = null,
        ?CarbonInterface $revokedAt = null,
    ): Integration {
        return Integration::updateOrCreate(
            ['user_id' => $user->id, 'provider' => $provider],
            [
                'status' => $status,
                'metadata' => $metadata,
                'connected_at' => $connectedAt,
                'revoked_at' => $revokedAt,
            ],
        );
    }
}

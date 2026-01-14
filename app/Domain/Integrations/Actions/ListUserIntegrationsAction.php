<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Actions;

use App\Domain\Integrations\Models\Integration;
use App\Models\User;
use Illuminate\Support\Collection;

final class ListUserIntegrationsAction
{
    public function handle(User $user): Collection
    {
        return Integration::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('provider');
    }
}

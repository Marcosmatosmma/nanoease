<?php

declare(strict_types=1);

namespace App\Domain\Automations\Actions;

use App\Domain\Automations\Models\Automation;
use App\Models\User;
use Illuminate\Support\Collection;

final class ListUserAutomationsAction
{
    public function handle(User $user): Collection
    {
        return Automation::query()
            ->with([
                'actions' => function ($query) {
                    $query->orderBy('position');
                },
                'event',
            ])
            ->where('user_id', $user->id)
            ->orderByDesc('updated_at')
            ->get();
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Tasks\Actions;

use App\Domain\Tasks\Models\Board;
use App\Domain\Tasks\Models\BoardList;

class StoreBoardListAction
{
    public function handle(Board $board, array $data): BoardList
    {
        $maxPosition = $board->lists()->max('position') ?? -1;

        return $board->lists()->create([
            'name' => $data['name'],
            'color' => $data['color'] ?? null,
            'position' => $maxPosition + 1,
            'is_default' => false,
        ]);
    }
}

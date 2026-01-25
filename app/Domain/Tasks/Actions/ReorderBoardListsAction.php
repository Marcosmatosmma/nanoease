<?php

declare(strict_types=1);

namespace App\Domain\Tasks\Actions;

use App\Domain\Tasks\Models\Board;

class ReorderBoardListsAction
{
    public function handle(Board $board, array $listIds): void
    {
        foreach ($listIds as $position => $listId) {
            $board->lists()
                ->where('id', $listId)
                ->update(['position' => $position]);
        }
    }
}

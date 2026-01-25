<?php

declare(strict_types=1);

namespace App\Domain\Tasks\Actions;

use App\Domain\Tasks\Models\BoardList;

class UpdateBoardListAction
{
    public function handle(BoardList $boardList, array $data): BoardList
    {
        $boardList->update($data);
        
        return $boardList->fresh();
    }
}

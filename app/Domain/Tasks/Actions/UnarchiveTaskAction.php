<?php

declare(strict_types=1);

namespace App\Domain\Tasks\Actions;

use App\Domain\Tasks\Models\Task;

class UnarchiveTaskAction
{
    public function handle(Task $task): Task
    {
        $task->update([
            'archived_at' => null,
        ]);
        
        return $task->fresh();
    }
}

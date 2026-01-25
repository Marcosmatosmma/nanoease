<?php

declare(strict_types=1);

namespace App\Domain\Tasks\Actions;

use App\Domain\Tasks\Models\Task;

class ArchiveTaskAction
{
    public function handle(Task $task): Task
    {
        $task->update([
            'archived_at' => now(),
        ]);
        
        return $task->fresh();
    }
}

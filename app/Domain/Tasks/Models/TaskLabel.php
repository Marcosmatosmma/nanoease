<?php

declare(strict_types=1);

namespace App\Domain\Tasks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Team;

/**
 * Model de Etiqueta (Label) de Tarefa
 * 
 * Labels categorizam e filtram tarefas
 * Exemplos: urgente, importante, bug, feature
 */
class TaskLabel extends Model
{
    protected $fillable = [
        'team_id',
        'name',
        'color',
        'description',
    ];

    /**
     * Label pertence a um team (multi-tenancy)
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Label pode estar em múltiplas tarefas
     */
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_task_label');
    }
}

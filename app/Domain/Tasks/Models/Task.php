<?php

declare(strict_types=1);

namespace App\Domain\Tasks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\User;
use App\Models\Team;

/**
 * Model de Tarefa (Card do Kanban)
 * 
 * Representa uma atividade executável
 * Pode ter origem: manual, automação, formulário ou sistema
 */
class Task extends Model
{
    protected $fillable = [
        'board_list_id',
        'user_id',
        'team_id',
        'assigned_to',
        'title',
        'description',
        'encrypted_data',
        'gmail_message_id',
        'due_date',
        'position',
        'source',
        'source_id',
        'source_metadata',
        'is_completed',
        'completed_at',
        'archived_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'position' => 'integer',
        'source_metadata' => 'array',
        'encrypted_data' => 'encrypted:array', // Laravel criptografa array automaticamente
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    /**
     * Tarefa pertence a uma lista
     */
    public function boardList(): BelongsTo
    {
        return $this->belongsTo(BoardList::class);
    }

    /**
     * Tarefa pertence a um usuário (criador)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Tarefa pertence a um team (multi-tenancy)
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Tarefa pode ter um responsável
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Tarefa pode ter múltiplas etiquetas
     */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(TaskLabel::class, 'task_task_label');
    }

    /**
     * Verifica se a tarefa está atrasada
     */
    public function isOverdue(): bool
    {
        return !$this->is_completed 
            && $this->due_date 
            && $this->due_date->isPast();
    }

    /**
     * Verifica se vence hoje
     */
    public function isDueToday(): bool
    {
        return !$this->is_completed 
            && $this->due_date 
            && $this->due_date->isToday();
    }

    /**
     * Verifica se a tarefa está arquivada
     */
    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * Scope para filtrar tarefas não arquivadas
     */
    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }
}

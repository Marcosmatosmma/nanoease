<?php

declare(strict_types=1);

namespace App\Domain\Tasks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model de Lista do Board (Coluna do Kanban)
 * 
 * Representa uma fase/status das tarefas
 * Exemplos: A Fazer, Em Andamento, Concluído
 */
class BoardList extends Model
{
    protected $fillable = [
        'board_id',
        'name',
        'color',
        'position',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'position' => 'integer',
    ];

    /**
     * Lista pertence a um board
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    /**
     * Lista tem múltiplas tarefas
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('position');
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Tasks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use App\Models\Team;

/**
 * Model de Board (Quadro Kanban)
 * 
 * Um board contém múltiplas listas organizadas
 * Multi-tenancy: cada board pertence a um team
 */
class Board extends Model
{
    protected $fillable = [
        'user_id',
        'team_id',
        'name',
        'description',
        'color',
        'position',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'position' => 'integer',
    ];

    /**
     * Board pertence a um usuário (criador)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Board pertence a um team (multi-tenancy)
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Board tem múltiplas listas
     */
    public function lists(): HasMany
    {
        return $this->hasMany(BoardList::class)->orderBy('position');
    }
}

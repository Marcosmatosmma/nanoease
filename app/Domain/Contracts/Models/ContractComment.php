<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\Team;

/**
 * Model de Comentário de Contrato
 * 
 * Representa comentários adicionados aos contratos
 */
class ContractComment extends Model
{
    protected $fillable = [
        'contract_id',
        'user_id',
        'team_id',
        'comment',
    ];

    /**
     * Comentário pertence a um contrato
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Comentário pertence a um usuário
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Comentário pertence a um team (multi-tenancy)
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}

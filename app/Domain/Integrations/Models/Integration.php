<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\Team;

/**
 * Model de Integração (Gmail, Google Sheets, etc)
 * 
 * Multi-tenancy: Cada integração pertence a um team específico
 * Um usuário pode ter múltiplas integrações em diferentes teams
 */
final class Integration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'team_id', // OBRIGATÓRIO - Multi-tenancy
        'provider',
        'status',
        'metadata',
        'connected_at',
        'revoked_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'connected_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /**
     * Accessor para obter access_token dos metadados
     * O token OAuth fica armazenado em metadata->token
     */
    public function getAccessTokenAttribute(): ?string
    {
        return $this->metadata['token'] ?? null;
    }

    /**
     * Integração pertence a um usuário
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Integração pertence a um team (multi-tenancy)
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}

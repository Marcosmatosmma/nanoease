<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

final class Integration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
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

    public function getAccessTokenAttribute(): ?string
    {
        return $this->metadata['token'] ?? null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

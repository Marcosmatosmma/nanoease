<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class IntegrationCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'client_id',
        'client_secret',
        'redirect_uri',
    ];

    protected $casts = [
        'client_id' => 'encrypted',
        'client_secret' => 'encrypted',
        'redirect_uri' => 'encrypted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

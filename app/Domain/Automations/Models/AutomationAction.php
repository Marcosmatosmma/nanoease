<?php

declare(strict_types=1);

namespace App\Domain\Automations\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AutomationAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'automation_id',
        'type',
        'config',
        'position',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class);
    }
}

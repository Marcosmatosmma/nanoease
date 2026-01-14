<?php

declare(strict_types=1);

namespace App\Domain\Automations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutomationTriggerType extends Model
{
    protected $fillable = [
        'automation_event_id',
        'key',
        'title',
        'description',
        'icon',
        'placeholder',
        'uses_ai',
        'validation_rules',
        'position',
        'active',
    ];

    protected $casts = [
        'automation_event_id' => 'integer',
        'uses_ai' => 'boolean',
        'active' => 'boolean',
        'position' => 'integer',
        'validation_rules' => 'array',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(AutomationEvent::class, 'automation_event_id');
    }

    public function automations(): HasMany
    {
        return $this->hasMany(Automation::class, 'trigger_type_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeForEvent($query, int $eventId)
    {
        return $query->where('automation_event_id', $eventId);
    }

    public function scopeUsesAI($query, bool $usesAI = true)
    {
        return $query->where('uses_ai', $usesAI);
    }
}

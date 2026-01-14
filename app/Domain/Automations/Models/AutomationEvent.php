<?php

declare(strict_types=1);

namespace App\Domain\Automations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutomationEvent extends Model
{
    protected $fillable = [
        'key',
        'title',
        'description',
        'icon',
        'category',
        'position',
        'active',
        'metadata',
    ];

    protected $casts = [
        'active' => 'boolean',
        'position' => 'integer',
        'metadata' => 'array',
    ];

    public function triggerTypes(): HasMany
    {
        return $this->hasMany(AutomationTriggerType::class, 'automation_event_id')
            ->where('active', true)
            ->orderBy('position');
    }

    public function automations(): HasMany
    {
        return $this->hasMany(Automation::class, 'automation_event_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}

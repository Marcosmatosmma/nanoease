<?php

declare(strict_types=1);

namespace App\Domain\Automations\Models;

use App\Domain\Integrations\Models\Integration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Automation extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';

    protected $fillable = [
        'user_id',
        'integration_id',
        'automation_event_id',
        'trigger_type_id',
        'rule_text',
        'plan_rule_text',
        'gmail_label',
        'status',
    ];

    protected $casts = [
        'automation_event_id' => 'integer',
        'trigger_type_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(AutomationEvent::class, 'automation_event_id');
    }

    public function triggerType(): BelongsTo
    {
        return $this->belongsTo(AutomationTriggerType::class, 'trigger_type_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(AutomationAction::class);
    }
}

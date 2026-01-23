<?php

declare(strict_types=1);

namespace App\Domain\Automations\Models;

use App\Domain\Integrations\Models\Integration;
use App\Models\User;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model de Automação (Evento -> Regra -> Ação)
 * 
 * Multi-tenancy: Cada automação pertence a um team específico
 * Isolamento de dados entre clientes no SaaS
 */
final class Automation extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';

    protected $fillable = [
        'user_id',
        'team_id', // OBRIGATÓRIO - Multi-tenancy
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

    /**
     * Automação pertence a um usuário
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Automação pertence a um team (multi-tenancy)
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Automação usa uma integração (Gmail, etc)
     */
    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    /**
     * Automação pertence a um evento (email_received, etc)
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(AutomationEvent::class, 'automation_event_id');
    }

    /**
     * Automação tem um tipo de gatilho (assunto contém, remetente específico, etc)
     */
    public function triggerType(): BelongsTo
    {
        return $this->belongsTo(AutomationTriggerType::class, 'trigger_type_id');
    }

    /**
     * Automação tem múltiplas ações (enviar email, criar tarefa, etc)
     */
    public function actions(): HasMany
    {
        return $this->hasMany(AutomationAction::class);
    }
}

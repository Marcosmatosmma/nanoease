<?php

declare(strict_types=1);

namespace App\Domain\Automations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use App\Models\Team;
use App\Domain\Integrations\Models\Integration;

/**
 * Model para envios em massa de email
 * 
 * Armazena informações do envio, estatísticas e status
 * Multi-tenancy: Cada envio pertence a um team específico
 */
class MassEmailSend extends Model
{
    protected $fillable = [
        'user_id',
        'team_id', // OBRIGATÓRIO - Multi-tenancy
        'integration_id',
        'automation_event_id',
        'name',
        'send_type',
        'scheduled_at',
        'subject',
        'body',
        'recipient_source',
        'email_column',
        'columns',
        'variable_mapping',
        'total_recipients',
        'valid_recipients',
        'invalid_recipients',
        'sent_count',
        'failed_count',
        'status',
        'started_at',
        'completed_at',
        'metadata',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'columns' => 'array',
        'variable_mapping' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Envio pertence a um usuário
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Envio pertence a um team (multi-tenancy)
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Envio usa uma integração Gmail
     */
    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    /**
     * Envio pertence a um evento de automação
     */
    public function automationEvent(): BelongsTo
    {
        return $this->belongsTo(AutomationEvent::class);
    }

    /**
     * Envio tem múltiplos destinatários
     * Cada um com status individual (sent, failed, pending)
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(MassEmailRecipient::class);
    }

    /**
     * Verifica se o envio já foi processado (concluído, falhou ou cancelado)
     * 
     * @return bool true se já foi finalizado
     */
    public function isProcessed(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'cancelled']);
    }

    /**
     * Verifica se está agendado para envio futuro
     * 
     * @return bool true se está agendado
     */
    public function isScheduled(): bool
    {
        return $this->send_type === 'scheduled' && $this->status === 'scheduled';
    }
}

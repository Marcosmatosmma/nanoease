<?php

declare(strict_types=1);

namespace App\Domain\Automations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model para destinatários de envio em massa
 * Armazena status individual de cada email
 */
class MassEmailRecipient extends Model
{
    protected $fillable = [
        'mass_email_send_id',
        'email',
        'data',
        'status',
        'error_message',
        'ignore_reason',
        'sent_at',
        'message_id',
        'metadata',
    ];

    protected $casts = [
        'data' => 'array',
        'metadata' => 'array',
        'sent_at' => 'datetime',
    ];

    /**
     * Relacionamento com envio em massa
     */
    public function massEmailSend(): BelongsTo
    {
        return $this->belongsTo(MassEmailSend::class);
    }

    /**
     * Marca como enviado com sucesso
     */
    public function markAsSent(string $messageId): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'message_id' => $messageId,
        ]);
    }

    /**
     * Marca como falha
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Marca como ignorado
     */
    public function markAsIgnored(string $reason): void
    {
        $this->update([
            'status' => 'ignored',
            'ignore_reason' => $reason,
        ]);
    }
}

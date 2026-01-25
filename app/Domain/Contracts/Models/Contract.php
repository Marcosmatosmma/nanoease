<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use App\Models\Team;

/**
 * Model de Contrato
 * 
 * Representa um contrato jurídico com vencimentos e alertas
 */
class Contract extends Model
{
    protected $fillable = [
        'team_id',
        'user_id',
        'name',
        'contract_type',
        'my_role',
        'contract_object',
        'contract_number',
        'contractor',
        'contractor_cpf_cnpj',
        'contracted',
        'contracted_cpf_cnpj',
        'start_date',
        'end_date',
        'auto_renewal',
        'amount',
        'currency',
        'payment_terms',
        'status',
        'ai_summary',
        'notes',
        // Campos de Nota Fiscal
        'invoice_contact_email',
        'invoice_contact_link',
        'invoice_system',
        'invoice_description',
        'invoice_internal_notes',
        'invoice_recipient_name',
        'invoice_recipient_cnpj',
        'invoice_state_registration',
        'invoice_recipient_address',
        'invoice_service_code',
        'invoice_due_day',
        'invoice_cnpj_api_data',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'auto_renewal' => 'boolean',
        'amount' => 'decimal:2',
        'invoice_cnpj_api_data' => 'array',
    ];

    /**
     * Contrato pertence a um team (multi-tenancy)
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Contrato pertence a um usuário (criador)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Contrato tem múltiplos documentos
     */
    public function documents(): HasMany
    {
        return $this->hasMany(ContractDocument::class);
    }

    /**
     * Contrato tem múltiplos alertas
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(ContractAlert::class);
    }

    /**
     * Contrato tem histórico de eventos
     */
    public function history(): HasMany
    {
        return $this->hasMany(ContractHistory::class)->orderBy('created_at', 'desc');
    }

    /**
     * Contrato tem múltiplas notas fiscais
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->orderBy('invoice_date', 'desc');
    }

    /**
     * Verifica se o contrato está vencido
     */
    public function isExpired(): bool
    {
        return $this->end_date && $this->end_date->isPast();
    }

    /**
     * Verifica se o contrato vence hoje
     */
    public function expirestoday(): bool
    {
        return $this->end_date && $this->end_date->isToday();
    }

    /**
     * Verifica se o contrato vence em X dias
     */
    public function expiresInDays(int $days): bool
    {
        if (!$this->end_date) {
            return false;
        }
        
        return $this->end_date->diffInDays(now()) <= $days && $this->end_date->isFuture();
    }

    /**
     * Scope para filtrar contratos ativos
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ativo');
    }

    /**
     * Scope para filtrar contratos vencidos
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'vencido');
    }

    /**
     * Scope para filtrar por tipo
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('contract_type', $type);
    }
}

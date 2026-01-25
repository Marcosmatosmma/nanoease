<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Team;
use App\Domain\Tasks\Models\Task;

/**
 * Model de Alerta de Contrato
 * 
 * Representa um alerta de vencimento configurado para um contrato
 */
class ContractAlert extends Model
{
    protected $fillable = [
        'contract_id',
        'team_id',
        'alert_type',
        'days_before',
        'triggered_at',
        'task_id',
        'is_active',
    ];

    protected $casts = [
        'triggered_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Alerta pertence a um contrato
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Alerta pertence a um team (multi-tenancy)
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Alerta pode ter uma tarefa associada
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Verifica se o alerta já foi disparado
     */
    public function isTriggered(): bool
    {
        return $this->triggered_at !== null;
    }

    /**
     * Verifica se o alerta deve ser disparado
     */
    public function shouldTrigger(): bool
    {
        if (!$this->is_active || $this->isTriggered()) {
            return false;
        }

        $contract = $this->contract;
        
        if (!$contract || !$contract->end_date) {
            return false;
        }

        $today = now()->startOfDay();
        $endDate = $contract->end_date->startOfDay();

        switch ($this->alert_type) {
            case 'before_expiration':
                $daysUntilExpiration = $today->diffInDays($endDate, false);
                return $daysUntilExpiration <= $this->days_before && $daysUntilExpiration >= 0;
                
            case 'on_expiration':
                return $today->equalTo($endDate);
                
            case 'after_expiration':
                return $today->greaterThan($endDate);
                
            default:
                return false;
        }
    }

    /**
     * Scope para filtrar alertas ativos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para filtrar alertas não disparados
     */
    public function scopeNotTriggered($query)
    {
        return $query->whereNull('triggered_at');
    }

    /**
     * Scope para filtrar alertas por tipo
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('alert_type', $type);
    }
}

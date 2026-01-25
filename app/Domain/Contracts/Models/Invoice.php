<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Models;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model para Notas Fiscais vinculadas a contratos
 */
class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'team_id',
        'uploaded_by',
        'pdf_path',
        'xml_path',
        'invoice_date',
        'due_date',
        'amount',
        'invoice_number',
        'description',
        'xml_data',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'xml_data' => 'array',
    ];

    /**
     * Relacionamento com contrato
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Relacionamento com team (multi-tenancy)
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Relacionamento com usuário que fez upload
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Retorna o tamanho formatado do arquivo PDF
     */
    public function getFormattedPdfSizeAttribute(): ?string
    {
        if (!$this->pdf_path) {
            return null;
        }

        $path = storage_path('app/private/' . $this->pdf_path);
        if (!file_exists($path)) {
            return null;
        }

        $bytes = filesize($path);
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Verifica se a nota está vencida
     */
    public function isOverdue(): bool
    {
        if (!$this->due_date) {
            return false;
        }

        return $this->due_date->isPast();
    }

    /**
     * Verifica se vence hoje
     */
    public function isDueToday(): bool
    {
        if (!$this->due_date) {
            return false;
        }

        return $this->due_date->isToday();
    }
}

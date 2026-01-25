<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Team;

/**
 * Model de Documento do Contrato
 * 
 * Representa um arquivo (PDF/DOCX) vinculado a um contrato
 */
class ContractDocument extends Model
{
    protected $fillable = [
        'contract_id',
        'team_id',
        'uploaded_by',
        'file_path',
        'file_name',
        'file_type',
        'mime_type',
        'file_size',
        'extracted_text',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    /**
     * Documento pertence a um contrato
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Documento pertence a um team (multi-tenancy)
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Retorna o tamanho do arquivo formatado
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Verifica se é um PDF
     */
    public function isPdf(): bool
    {
        return strtolower($this->file_type) === 'pdf';
    }

    /**
     * Verifica se é um DOCX
     */
    public function isDocx(): bool
    {
        return in_array(strtolower($this->file_type), ['docx', 'doc']);
    }
}

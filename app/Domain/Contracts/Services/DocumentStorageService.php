<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Services;

use App\Domain\Contracts\Models\Contract;
use App\Domain\Contracts\Models\ContractDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Service para armazenamento seguro de documentos contratuais
 * 
 * Gerencia upload, storage e exclusão com isolamento multi-tenant
 */
class DocumentStorageService
{
    /**
     * Armazena documento do contrato
     * 
     * @param Contract $contract
     * @param UploadedFile $file
     * @return ContractDocument
     * @throws \InvalidArgumentException
     */
    public function store(Contract $contract, UploadedFile $file): ContractDocument
    {
        // Validar tipo de arquivo
        $this->validateFileType($file);
        
        // Gerar path seguro com isolamento por team
        $path = $this->generateSecurePath($contract->team_id, $contract->id, $file);
        
        // Armazenar arquivo
        $storedPath = Storage::disk('local')->putFileAs(
            dirname($path),
            $file,
            basename($path)
        );
        
        if (!$storedPath) {
            throw new \RuntimeException('Falha ao armazenar documento');
        }
        
        // Criar registro no banco
        return ContractDocument::create([
            'contract_id' => $contract->id,
            'team_id' => $contract->team_id,
            'file_path' => $storedPath,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'uploaded_at' => now(),
        ]);
    }

    /**
     * Exclui documento do storage e banco
     */
    public function delete(ContractDocument $document): bool
    {
        // Excluir arquivo físico
        if (Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }
        
        // Excluir registro
        return $document->delete();
    }

    /**
     * Retorna URL temporária para download/visualização
     */
    public function getTemporaryUrl(ContractDocument $document, int $minutes = 30): string
    {
        // Para storage local, retornar path temporário
        // Em produção com S3, usar temporaryUrl()
        return Storage::disk('local')->url($document->file_path);
    }

    /**
     * Retorna conteúdo do arquivo
     */
    public function getContent(ContractDocument $document): string
    {
        if (!Storage::disk('local')->exists($document->file_path)) {
            throw new \RuntimeException('Arquivo não encontrado');
        }
        
        return Storage::disk('local')->get($document->file_path);
    }

    /**
     * Valida tipo de arquivo permitido
     */
    private function validateFileType(UploadedFile $file): void
    {
        $allowedMimeTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
        
        $allowedExtensions = ['pdf', 'doc', 'docx'];
        
        if (!in_array($file->getMimeType(), $allowedMimeTypes) ||
            !in_array(strtolower($file->getClientOriginalExtension()), $allowedExtensions)) {
            throw new \InvalidArgumentException('Tipo de arquivo não permitido. Use PDF ou DOCX.');
        }
        
        // Validar tamanho (max 20MB)
        if ($file->getSize() > 20 * 1024 * 1024) {
            throw new \InvalidArgumentException('Arquivo muito grande. Tamanho máximo: 20MB.');
        }
    }

    /**
     * Gera path seguro com isolamento multi-tenant
     */
    private function generateSecurePath(int $teamId, int $contractId, UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $timestamp = now()->timestamp;
        $random = Str::random(8);
        
        return sprintf(
            'contracts/team_%d/contract_%d/%s_%s_%s.%s',
            $teamId,
            $contractId,
            $fileName,
            $timestamp,
            $random,
            $extension
        );
    }
}

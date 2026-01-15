<?php

declare(strict_types=1);

namespace App\Domain\Automations\Actions;

use App\Domain\Automations\Models\ClassifiedEmail;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportClassifiedEmailsAction
{
    public function handle(int $userId, ?int $automationId = null): StreamedResponse
    {
        $query = ClassifiedEmail::query()
            ->where('user_id', $userId)
            ->with(['automation'])
            ->orderBy('email_date', 'desc');

        if ($automationId) {
            $query->where('automation_id', $automationId);
        }

        $filename = 'emails-organizados-' . now()->format('Y-m-d-His') . '.csv';

        return Response::streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // Header do CSV
            fputcsv($handle, [
                'Data',
                'Remetente',
                'Assunto',
                'Label Gmail',
                'Automação',
                'Tipo',
                'Valor',
                'Moeda',
                'Vencimento',
                'Nº Documento',
                'CNPJ',
                'CPF',
                'Empresa',
                'Banco',
                'Status',
                'Código de Barras',
                'Observações',
                'Gmail ID',
            ]);

            // Processar em chunks para não estourar memória
            $query->chunk(100, function ($emails) use ($handle) {
                foreach ($emails as $email) {
                    $metadata = $email->metadata ?? [];

                    fputcsv($handle, [
                        $email->email_date?->format('d/m/Y H:i:s') ?? '',
                        $email->email_from ?? '',
                        $email->email_subject ?? '',
                        $email->gmail_label ?? '',
                        $email->automation?->rule_text ?? '',
                        $metadata['tipo'] ?? '',
                        $metadata['valor'] ?? '',
                        $metadata['moeda'] ?? '',
                        $metadata['vencimento'] ?? '',
                        $metadata['numero_documento'] ?? '',
                        $metadata['cnpj'] ?? '',
                        $metadata['cpf'] ?? '',
                        $metadata['empresa'] ?? '',
                        $metadata['banco'] ?? '',
                        $metadata['status'] ?? '',
                        $metadata['codigo_barras'] ?? '',
                        $metadata['observacoes'] ?? '',
                        $email->gmail_id ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}

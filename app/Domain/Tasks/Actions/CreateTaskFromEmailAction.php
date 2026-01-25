<?php

declare(strict_types=1);

namespace App\Domain\Tasks\Actions;

use App\Domain\Tasks\Models\Task;
use App\Domain\Tasks\Models\BoardList;

/**
 * Action para criar tarefa a partir de email recebido via automação
 * 
 * Converte dados do email em uma tarefa no Kanban
 * Respeita multi-tenancy e isolamento de dados
 */
class CreateTaskFromEmailAction
{
    /**
     * Cria tarefa a partir de dados do email
     * 
     * @param array $email Dados do email (subject, from, body, etc)
     * @param array $config Configuração da automação (board_list_id, assigned_to, etc)
     * @param int $teamId ID do team (multi-tenancy)
     * @param int $userId ID do usuário que criou a automação
     * @return Task Tarefa criada
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Se lista não existir ou não pertencer ao team
     */
    public function handle(array $email, array $config, int $teamId, int $userId): Task
    {
        // Validar board_list_id e permissão (multi-tenancy)
        $boardList = BoardList::where('id', $config['board_list_id'])
            ->whereHas('board', function ($query) use ($teamId) {
                $query->where('team_id', $teamId);
            })
            ->firstOrFail();
        
        // Calcular próxima posição na lista
        $maxPosition = Task::where('board_list_id', $boardList->id)
            ->where('team_id', $teamId)
            ->max('position') ?? -1;
        
        // Montar descrição com dados do email
        $description = $this->buildDescription($email, $config);
        
        // Definir vencimento automático se configurado
        $dueDate = null;
        if ($config['auto_due_date'] ?? false) {
            $dueDate = now()->addDays(7)->format('Y-m-d');
        }
        
        // Preparar dados criptografados (corpo do email, metadados sensíveis)
        $encryptedData = null;
        if ($config['include_email_body'] ?? false) {
            $encryptedData = [
                'email_body' => $email['body'] ?? $email['snippet'] ?? '',
                'email_from' => $email['from'] ?? null,
                'email_subject' => $email['subject'] ?? null,
                'stored_at' => now()->toIso8601String(),
            ];
        }
        
        // Criar tarefa
        return Task::create([
            'user_id' => $userId,
            'team_id' => $teamId,
            'board_list_id' => $boardList->id,
            'title' => $this->sanitizeTitle($email['subject'] ?? 'Email sem assunto'),
            'description' => $description,
            'encrypted_data' => $encryptedData, // Laravel criptografa automaticamente
            'gmail_message_id' => $email['gmail_id'] ?? $email['message_id'] ?? null,
            'source' => 'automation',
            'source_id' => $email['id'] ?? null,
            'position' => $maxPosition + 1,
            'assigned_to' => $config['assigned_to'] ?? null,
            'due_date' => $dueDate,
            'is_completed' => false,
        ]);
    }
    
    /**
     * Monta descrição da tarefa com dados do email
     */
    private function buildDescription(array $email, array $config): string
    {
        $parts = [];
        
        if ($config['include_sender'] ?? true) {
            $from = $email['from'] ?? 'Desconhecido';
            $parts[] = '<p><strong>De:</strong> ' . htmlspecialchars($from) . '</p>';
        }
        
        if ($config['include_date'] ?? true) {
            $date = isset($email['date']) 
                ? date('d/m/Y H:i', strtotime($email['date']))
                : now()->format('d/m/Y H:i');
            $parts[] = '<p><strong>Data:</strong> ' . $date . '</p>';
        }
        
        // Link para o email original no Gmail
        if (!empty($email['gmail_id'])) {
            $gmailUrl = 'https://mail.google.com/mail/u/0/#inbox/' . $email['gmail_id'];
            $parts[] = '<p><a href="' . $gmailUrl . '" target="_blank" rel="noopener" style="color: #2563eb; text-decoration: underline;">📧 Ver email original no Gmail</a></p>';
        } elseif (!empty($email['message_id'])) {
            // Fallback: busca por Message-ID (menos confiável)
            $searchUrl = 'https://mail.google.com/mail/u/0/#search/rfc822msgid:' . urlencode($email['message_id']);
            $parts[] = '<p><a href="' . $searchUrl . '" target="_blank" rel="noopener" style="color: #2563eb; text-decoration: underline;">📧 Buscar email no Gmail</a></p>';
        }
        
        if (!empty($parts)) {
            $parts[] = '<hr>';
        }
        
        // Só inclui corpo do email se configurado (privacidade LGPD)
        if ($config['include_email_body'] ?? false) {
            $body = $email['body'] ?? $email['snippet'] ?? '';
            $parts[] = $this->sanitizeBody($body);
        } else {
            $parts[] = '<p class="text-gray-500 italic">Corpo do email não incluído (configuração de privacidade)</p>';
        }
        
        return implode("\n", $parts);
    }
    
    /**
     * Sanitiza título removendo quebras de linha
     */
    private function sanitizeTitle(string $title): string
    {
        $title = trim($title);
        $title = str_replace(["\r", "\n"], ' ', $title);
        $title = preg_replace('/\s+/', ' ', $title);
        
        // Limita tamanho do título
        if (mb_strlen($title) > 255) {
            $title = mb_substr($title, 0, 252) . '...';
        }
        
        return $title;
    }
    
    /**
     * Sanitiza corpo do email
     */
    private function sanitizeBody(string $body): string
    {
        // Limpa HTML perigoso mas mantém formatação básica
        $body = $this->cleanHtml($body);
        
        // Limita tamanho
        if (mb_strlen($body) > 10000) {
            $body = mb_substr($body, 0, 9997) . '...';
        }
        
        return trim($body);
    }
    
    /**
     * Limpa HTML mantendo apenas tags seguras para formatação
     */
    private function cleanHtml(string $html): string
    {
        // Remove scripts, styles e outros elementos perigosos
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
        $html = preg_replace('/<iframe\b[^>]*>(.*?)<\/iframe>/is', '', $html);
        
        // Remove atributos perigosos (onclick, onerror, etc)
        $html = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);
        
        // Remove imagens inline grandes (assinaturas do Gmail, etc)
        $html = preg_replace_callback('/<img[^>]*>/', function($matches) {
            if (preg_match('/width\s*=\s*["\']?(\d+)["\']?/', $matches[0], $width)) {
                // Remove imagens maiores que 100px de largura
                if (intval($width[1]) > 100) {
                    return '';
                }
            }
            return $matches[0];
        }, $html);
        
        // Normaliza quebras de linha
        $html = str_replace("\r\n", "\n", $html);
        
        return $html;
    }
}

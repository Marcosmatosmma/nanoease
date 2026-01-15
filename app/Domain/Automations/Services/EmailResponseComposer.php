<?php

declare(strict_types=1);

namespace App\Domain\Automations\Services;

use Illuminate\Support\Str;

/**
 * Serviço para compor respostas de e-mail substituindo variáveis dinâmicas
 * 
 * Variáveis disponíveis:
 * - {from_name}: Nome do remetente
 * - {from_email}: E-mail do remetente
 * - {subject}: Assunto original
 * - {date}: Data/hora atual
 * - {body_preview}: Prévia do corpo (100 caracteres)
 */
final class EmailResponseComposer
{
    /**
     * Compõe texto substituindo variáveis pelos dados reais do evento
     *
     * @param string $template Template com variáveis (ex: "Olá {from_name}")
     * @param array $event Dados do e-mail recebido
     * @return string Texto com variáveis substituídas
     */
    public function compose(string $template, array $event): string
    {
        $variables = $this->buildVariables($event);
        
        return str_replace(
            array_keys($variables),
            array_values($variables),
            $template
        );
    }

    /**
     * Constrói array de variáveis baseado no evento
     *
     * @param array $event Dados do e-mail
     * @return array Mapa de variáveis
     */
    private function buildVariables(array $event): array
    {
        $from = $event['from'] ?? '';
        
        return [
            '{from_name}' => $this->extractName($from),
            '{from_email}' => $this->extractEmail($from),
            '{subject}' => $event['subject'] ?? '(sem assunto)',
            '{date}' => now()->format('d/m/Y H:i'),
            '{body_preview}' => $this->extractBodyPreview($event['body'] ?? ''),
        ];
    }

    /**
     * Extrai nome do campo From
     * 
     * Ex: "João Silva <joao@email.com>" -> "João Silva"
     *     "joao@email.com" -> "joao@email.com"
     *
     * @param string $from Campo From do e-mail
     * @return string Nome extraído
     */
    private function extractName(string $from): string
    {
        // Tenta extrair nome antes do <email>
        if (preg_match('/^(.+?)\s*</', $from, $matches)) {
            return trim($matches[1], ' "\'');
        }
        
        // Se não tiver nome, retorna o e-mail
        return $this->extractEmail($from);
    }

    /**
     * Extrai e-mail do campo From
     * 
     * Ex: "João Silva <joao@email.com>" -> "joao@email.com"
     *     "joao@email.com" -> "joao@email.com"
     *
     * @param string $from Campo From do e-mail
     * @return string E-mail extraído
     */
    private function extractEmail(string $from): string
    {
        // Tenta extrair e-mail de dentro de < >
        if (preg_match('/<(.+?)>/', $from, $matches)) {
            return $matches[1];
        }
        
        // Se não tiver < >, assume que é o e-mail direto
        return trim($from);
    }

    /**
     * Extrai prévia do corpo do e-mail
     * 
     * Remove tags HTML e limita a 100 caracteres
     *
     * @param string $body Corpo do e-mail
     * @return string Prévia
     */
    private function extractBodyPreview(string $body): string
    {
        // Remove tags HTML
        $text = strip_tags($body);
        
        // Remove espaços extras
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        // Limita a 100 caracteres
        return Str::limit($text, 100);
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Automations\Services;

use App\Domain\Integrations\Models\Integration;
use App\Domain\Automations\Models\MassEmailRecipient;
use Illuminate\Support\Facades\Http;

/**
 * Service responsável por enviar emails via Gmail API
 * 
 * Responsabilidades:
 * - Substituir variáveis {{nome}}, {{empresa}} nos templates
 * - Construir mensagem no formato RFC 2822 (padrão de email)
 * - Enviar via Gmail API com encoding correto
 * - Renovar token OAuth automaticamente quando expira
 * - Tratar erros e retornar resultado estruturado
 * 
 * Uso:
 * - Chamado pelo ProcessMassEmailSendJob para cada destinatário
 * - Recebe Integration (Gmail), Recipient, subject e body templates
 * - Retorna array com success, message_id e error
 */
class MassEmailSenderService
{
    /**
     * URL da Gmail API para envio de mensagens
     * Endpoint: POST /gmail/v1/users/me/messages/send
     */
    private const SEND_URL = 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send';

    /**
     * Envia email personalizado para um destinatário via Gmail API
     * 
     * Processo completo:
     * 1. Substitui variáveis {{nome}}, {{empresa}} com dados do destinatário
     * 2. Monta mensagem RFC 2822 (To, From, Subject, Body)
     * 3. Codifica em base64 URL-safe (exigido pela Gmail API)
     * 4. Envia POST para Gmail API com token OAuth
     * 5. Se 401 (token expirado), renova token e tenta novamente
     * 6. Retorna resultado estruturado
     * 
     * @param Integration $integration Integração Gmail do usuário (contém access_token)
     * @param MassEmailRecipient $recipient Destinatário com email e dados (nome, empresa, etc)
     * @param string $subject Template do assunto (ex: "Oi {{nome}}")
     * @param string $body Template do corpo (ex: "Sua empresa {{empresa}} foi selecionada")
     * @return array ['success' => bool, 'message_id' => string|null, 'error' => string|null]
     */
    public function send(
        Integration $integration,
        MassEmailRecipient $recipient,
        string $subject,
        string $body
    ): array {
        try {
            // Substitui variáveis {{nome}}, {{empresa}} etc com valores reais do destinatário
            $personalizedSubject = $this->replaceVariables($subject, $recipient->data ?? []);
            $personalizedBody = $this->replaceVariables($body, $recipient->data ?? []);

            // Monta o email no formato RFC 2822 (padrão MIME de mensagens)
            $rawMessage = $this->buildRawMessage(
                to: $recipient->email,
                subject: $personalizedSubject,
                body: $personalizedBody
            );

            // Codifica em base64 URL-safe (exigido pela Gmail API - RFC 4648)
            $encodedMessage = $this->base64UrlEncode($rawMessage);

            // Log para debug: mostra preview da mensagem sendo enviada
            \Log::info('Enviando email via Gmail API', [
                'to' => $recipient->email,
                'subject' => $personalizedSubject,
                'raw_message_preview' => substr($rawMessage, 0, 200),
                'encoded_preview' => substr($encodedMessage, 0, 100),
                'token_prefix' => substr($integration->access_token, 0, 20),
            ]);

            // Envia POST para Gmail API com token de autenticação
            // Body: { "raw": "base64_url_safe_encoded_message" }
            $response = Http::withToken($integration->access_token)
                ->timeout(30) // Timeout de 30 segundos
                ->post(self::SEND_URL, [
                    'raw' => $encodedMessage,
                ]);

            // Se status 401 = token OAuth expirado, tenta renovar
            if ($response->status() === 401) {
                $renewed = $this->renewToken($integration);
                
                // Se conseguiu renovar, tenta enviar novamente com novo token
                if ($renewed) {
                    $response = Http::withToken($integration->fresh()->access_token)
                        ->timeout(30)
                        ->post(self::SEND_URL, [
                            'raw' => $this->base64UrlEncode($rawMessage),
                        ]);
                }
            }

            // Se falhou (status diferente de 2xx), loga erro e retorna falha
            if (!$response->successful()) {
                \Log::error('Gmail API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'email' => $recipient->email,
                ]);
                
                return [
                    'success' => false,
                    'message_id' => null,
                    'error' => 'Erro ao enviar: ' . $response->status() . ' - ' . $response->body(),
                ];
            }

            // Sucesso! Extrai ID da mensagem enviada
            $data = $response->json();

            return [
                'success' => true,
                'message_id' => $data['id'] ?? null, // ID único da mensagem no Gmail
                'error' => null,
            ];

        } catch (\Exception $e) {
            // Captura qualquer exceção (rede, timeout, etc)
            return [
                'success' => false,
                'message_id' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Renova o access token do Gmail usando refresh token
     * 
     * Processo:
     * - Busca refresh_token nos metadados da integração
     * - Faz POST para OAuth2 do Google
     * - Atualiza access_token nos metadados
     * - Salva timestamp de quando foi renovado
     * 
     * O refresh_token é permanente (não expira), mas access_token expira em ~1h.
     * Gmail API retorna 401 quando access_token expira, então renovamos automaticamente.
     * 
     * @param Integration $integration Integração Gmail
     * @return bool true se conseguiu renovar, false caso contrário
     */
    protected function renewToken(Integration $integration): bool
    {
        // Busca refresh_token dos metadados
        $metadata = $integration->metadata ?? [];
        $refreshToken = $metadata['refresh_token'] ?? null;

        // Se não tem refresh_token, não pode renovar
        if (!$refreshToken) {
            return false;
        }

        // Faz POST para endpoint OAuth2 do Google para renovar token
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        // Se falhou, retorna false
        if (!$response->successful()) {
            return false;
        }

        // Extrai novo access_token da resposta
        $data = $response->json();
        
        // Atualiza metadados com novo token
        $metadata['token'] = $data['access_token'] ?? null;
        $metadata['refreshed_at'] = now()->timestamp; // Timestamp de quando foi renovado

        // Salva no banco
        $integration->update(['metadata' => $metadata]);

        return true;
    }

    /**
     * Substitui variáveis {{nome}}, {{empresa}} etc pelos valores reais
     * 
     * Exemplo:
     * Template: "Olá {{nome}}, sua empresa {{empresa}} foi selecionada"
     * Data: ['nome' => 'João', 'empresa' => 'ACME Corp']
     * Result: "Olá João, sua empresa ACME Corp foi selecionada"
     * 
     * Funciona tanto no assunto quanto no corpo do email.
     * Cada coluna do CSV vira uma variável disponível.
     * 
     * @param string $template Template com {{variáveis}}
     * @param array $data Dados do destinatário (colunas do CSV)
     * @return string Texto com variáveis substituídas
     */
    protected function replaceVariables(string $template, array $data): string
    {
        $result = $template;

        // Itera sobre cada campo do CSV (nome, empresa, etc)
        foreach ($data as $key => $value) {
            // Substitui {{key}} pelo valor
            // Ex: {{nome}} -> João
            $result = str_replace("{{" . $key . "}}", $value, $result);
        }

        return $result;
    }

    /**
     * Monta mensagem de email no formato RFC 2822 (padrão MIME)
     * 
     * RFC 2822 define o formato de emails na internet:
     * - Headers obrigatórios: From, To, Subject
     * - Headers opcionais: MIME-Version, Content-Type, etc
     * - Linha em branco separando headers do corpo
     * - Corpo do email
     * 
     * Gmail API exige que a mensagem esteja neste formato.
     * 
     * Encoding UTF-8:
     * - Subject usa MIME encoded-word: =?UTF-8?B?base64?=
     * - Body usa Content-Type: text/plain; charset=utf-8
     * - Isso garante acentos, emojis, etc funcionem corretamente
     * 
     * @param string $to Email do destinatário
     * @param string $subject Assunto (já personalizado)
     * @param string $body Corpo (já personalizado)
     * @return string Mensagem RFC 2822 completa
     */
    protected function buildRawMessage(string $to, string $subject, string $body): string
    {
        // Codifica o assunto em UTF-8 usando MIME encoded-word (RFC 2047)
        // Formato: =?charset?encoding?encoded-text?=
        // Exemplo: =?UTF-8?B?T2zDoQ==?= (representa "Olá" em base64)
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        
        // Monta headers RFC 2822
        // IMPORTANTE: Cada header termina com \r\n (CRLF)
        $message = "From: me\r\n"; // Gmail API usa "me" como remetente (usuário autenticado)
        $message .= "To: {$to}\r\n";
        $message .= "Subject: {$encodedSubject}\r\n";
        $message .= "MIME-Version: 1.0\r\n"; // Declara que usa MIME
        $message .= "Content-Type: text/plain; charset=utf-8\r\n"; // Email texto com UTF-8
        $message .= "Content-Transfer-Encoding: 8bit\r\n"; // Codificação 8bit para UTF-8
        $message .= "\r\n"; // Linha em branco separa headers do corpo (OBRIGATÓRIO)
        $message .= $body; // Corpo do email

        return $message;
    }

    /**
     * Codifica string em base64 URL-safe (RFC 4648)
     * 
     * Gmail API exige base64 URL-safe, não o base64 padrão:
     * - base64 padrão usa: + / =
     * - base64 URL-safe usa: - _ (sem padding)
     * 
     * Conversão:
     * - + vira -
     * - / vira _
     * - Remove = (padding)
     * 
     * Isso evita problemas com URLs, pois + e / têm significado especial em URLs.
     * 
     * @param string $data Dados para codificar (mensagem RFC 2822)
     * @return string Base64 URL-safe (sem padding)
     */
    protected function base64UrlEncode(string $data): string
    {
        // base64_encode() -> codifica em base64 padrão
        // strtr() -> substitui + por - e / por _
        // rtrim() -> remove padding = do final
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

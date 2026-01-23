<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Services;

use App\Domain\Integrations\Models\Integration;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

final class GmailMessageFetcher
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const LIST_URL = 'https://gmail.googleapis.com/gmail/v1/users/me/messages';
    private const GET_URL = 'https://gmail.googleapis.com/gmail/v1/users/me/messages/%s';

    public function fetch(Integration $integration, string $query, int $limit = 5): array
    {
        $token = $integration->access_token;
        
        if (! $token) {
            return ['status' => 'error', 'message' => 'Token do Gmail ausente ou inválido.'];
        }

        $listResponse = Http::withToken($token)->timeout(30)->get(self::LIST_URL, [
            'q' => $query,
            'maxResults' => $limit,
            'includeSpamTrash' => false,
        ]);

        // Se 401 e temos refresh_token, tentar refresh
        if ($listResponse->status() === 401 && $this->hasRefreshToken($integration)) {
            \Log::info('Token expirado, tentando refresh...', [
                'has_refresh_token' => $this->hasRefreshToken($integration),
            ]);
            
            $newToken = $this->refreshAccessToken($integration);
            
            \Log::info('Resultado do refresh:', [
                'success' => $newToken ? 'SIM' : 'NÃO',
                'new_token_preview' => $newToken ? substr($newToken, 0, 20) . '...' : null,
            ]);
            
            if ($newToken) {
                $token = $newToken;
                $listResponse = Http::withToken($token)->timeout(30)->get(self::LIST_URL, [
                    'q' => $query,
                    'maxResults' => $limit,
                    'includeSpamTrash' => false,
                ]);
                
                \Log::info('Tentativa após refresh:', [
                    'status' => $listResponse->status(),
                ]);
            }
        }

        if (! $listResponse->successful()) {
            return [
                'status' => 'error',
                'message' => 'Falha ao listar mensagens do Gmail.',
                'context' => [
                    'status' => $listResponse->status(),
                    'body' => $listResponse->json(),
                ],
            ];
        }

        $messages = [];
        foreach ($listResponse->json('messages', []) as $item) {
            $msg = $this->getMessage($token, $item['id'] ?? null);
            if ($msg) {
                $messages[] = $msg;
            }
        }

        return ['status' => 'ok', 'messages' => $messages];
    }

    private function getMessage(string $token, ?string $id): ?array
    {
        if (! $id) {
            return null;
        }

        $response = Http::withToken($token)->timeout(30)->get(sprintf(self::GET_URL, $id), [
            'format' => 'full',
        ]);

        if (! $response->successful()) {
            return null;
        }

        $headers = collect($response->json('payload.headers', []))->keyBy(fn ($h) => $h['name'] ?? '');
        
        $body = $this->extractBody($response->json('payload'));

        return [
            'id' => $id,  // Gmail API message ID (para aplicar labels)
            'gmail_id' => $id,  // Alias mais claro
            'message_id' => $headers->get('Message-ID')['value'] ?? $headers->get('Message-Id')['value'] ?? null,
            'from' => $headers->get('From')['value'] ?? null,
            'subject' => $headers->get('Subject')['value'] ?? null,
            'date' => $headers->get('Date')['value'] ?? null,
            'snippet' => $response->json('snippet'),
            'body' => $body,
        ];
    }

    private function extractBody(array $payload): string
    {
        if (isset($payload['body']['data']) && !empty($payload['body']['data'])) {
            return $this->decodeBody($payload['body']['data']);
        }

        if (isset($payload['parts']) && is_array($payload['parts'])) {
            foreach ($payload['parts'] as $part) {
                if (($part['mimeType'] ?? '') === 'text/html' && isset($part['body']['data'])) {
                    return $this->decodeBody($part['body']['data']);
                }
            }

            foreach ($payload['parts'] as $part) {
                if (($part['mimeType'] ?? '') === 'text/plain' && isset($part['body']['data'])) {
                    return $this->decodeBody($part['body']['data']);
                }
            }

            foreach ($payload['parts'] as $part) {
                if (isset($part['parts'])) {
                    $nested = $this->extractBody($part);
                    if ($nested) {
                        return $nested;
                    }
                }
            }
        }

        return '';
    }

    private function decodeBody(string $data): string
    {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }

    private function hasRefreshToken(Integration $integration): bool
    {
        return (bool) Arr::get($integration->metadata ?? [], 'refresh_token');
    }

    private function refreshAccessToken(Integration $integration): ?string
    {
        $metadata = $integration->metadata ?? [];
        $refreshToken = Arr::get($metadata, 'refresh_token');

        if (! $refreshToken) {
            return null;
        }

        $response = Http::asForm()->post(self::TOKEN_URL, [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        $metadata['token'] = $data['access_token'] ?? null;
        $metadata['expires_in'] = $data['expires_in'] ?? $metadata['expires_in'] ?? null;
        $metadata['refreshed_at'] = CarbonImmutable::now()->timestamp;

        $integration->update(['metadata' => $metadata]);

        return $metadata['token'] ?? null;
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Services;

use App\Domain\Integrations\Models\Integration;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class GmailEmailSender
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const SEND_URL = 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send';

    public function send(Integration $integration, string $from, array $recipients, string $subject, string $body): array
    {
        $token = $this->resolveAccessToken($integration);

        if (! $token) {
            return [
                'status' => 'ignored',
                'message' => 'Token do Gmail ausente ou inválido.',
                'provider' => 'gmail',
            ];
        }

        $rawMessage = $this->buildRawMessage($from, $recipients, $subject, $body);
        $response = Http::withToken($token)->post(self::SEND_URL, ['raw' => $rawMessage]);

        if ($response->status() === 401 && $this->shouldRefresh($integration)) {
            $token = $this->refreshAccessToken($integration);
            if ($token) {
                $response = Http::withToken($token)->post(self::SEND_URL, ['raw' => $rawMessage]);
            }
        }

        if ($response->status() === 403 && data_get($response->json(), 'error.reason') === 'accessNotConfigured') {
            return [
                'status' => 'error',
                'message' => 'Gmail API desabilitada no projeto. Ative em console.developers.google.com e tente novamente.',
                'provider' => 'gmail',
                'context' => [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ],
            ];
        }

        if (! $response->successful()) {
            return [
                'status' => 'error',
                'message' => 'Falha ao enviar via Gmail API.',
                'provider' => 'gmail',
                'context' => [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ],
            ];
        }

        return [
            'status' => 'executed',
            'message' => 'Enviado via Gmail API.',
            'provider' => 'gmail',
            'context' => [
                'gmail_id' => $response->json('id'),
            ],
        ];
    }

    private function buildRawMessage(string $from, array $recipients, string $subject, string $body): string
    {
        $isHtml = strip_tags($body) !== $body;
        $contentType = $isHtml ? 'text/html; charset=UTF-8' : 'text/plain; charset=UTF-8';
        
        // Encode subject para MIME encoded-word se tiver caracteres especiais
        $encodedSubject = $this->encodeSubject($subject);
        
        $headers = [
            'From: '.$from,
            'To: '.implode(', ', $recipients),
            'Subject: '.$encodedSubject,
            'Reply-To: '.$from,
            'MIME-Version: 1.0',
            'Content-Type: '.$contentType,
        ];

        $raw = implode("\r\n", $headers)."\r\n\r\n".$body;

        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    /**
     * Encode subject para MIME encoded-word (RFC 2047)
     */
    private function encodeSubject(string $subject): string
    {
        // Se não tem caracteres especiais, retorna direto
        if (mb_check_encoding($subject, 'ASCII')) {
            return $subject;
        }

        // Encode para base64 (mais seguro que quoted-printable para UTF-8)
        return '=?UTF-8?B?' . base64_encode($subject) . '?=';
    }

    private function resolveAccessToken(Integration $integration): ?string
    {
        $metadata = $integration->metadata ?? [];

        if ($this->tokenIsFresh($metadata)) {
            return $metadata['token'] ?? null;
        }

        return $this->refreshAccessToken($integration);
    }

    private function tokenIsFresh(array $metadata): bool
    {
        $token = $metadata['token'] ?? null;
        $expiresIn = $metadata['expires_in'] ?? null;
        $refreshedAt = $metadata['refreshed_at'] ?? null;

        if (! $token || ! $expiresIn || ! $refreshedAt) {
            return false;
        }

        $refreshed = CarbonImmutable::createFromTimestamp((int) $refreshedAt);
        $expiresAt = $refreshed->addSeconds((int) $expiresIn - 60);

        return CarbonImmutable::now()->lessThan($expiresAt);
    }

    private function shouldRefresh(Integration $integration): bool
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

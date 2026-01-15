<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Services;

use App\Domain\Integrations\Models\Integration;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Gerenciador de Labels do Gmail
 * 
 * Responsável por criar, listar e aplicar labels no Gmail via API.
 */
final class GmailLabelManager
{
    private const GMAIL_API_BASE = 'https://gmail.googleapis.com/gmail/v1';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    /**
     * Cria um novo label no Gmail
     *
     * @param Integration $integration Integração do Gmail
     * @param string $labelName Nome do label (ex: "Financeiro/Boletos")
     * @return array ['success' => bool, 'label_id' => string|null, 'message' => string]
     */
    public function createLabel(Integration $integration, string $labelName): array
    {
        try {
            $accessToken = $integration->access_token;

            $response = Http::withToken($accessToken)
                ->post(self::GMAIL_API_BASE . '/users/me/labels', [
                    'name' => $labelName,
                    'labelListVisibility' => 'labelShow',
                    'messageListVisibility' => 'show',
                ]);

            // Se 401 e temos refresh_token, tentar refresh
            if ($response->status() === 401 && $this->hasRefreshToken($integration)) {
                $newToken = $this->refreshAccessToken($integration);
                
                if ($newToken) {
                    $integration->refresh();
                    return $this->createLabel($integration, $labelName);
                }
            }

            if ($response->successful()) {
                $labelId = $response->json('id');
                
                Log::info('Gmail label created', [
                    'label_name' => $labelName,
                    'label_id' => $labelId,
                ]);

                return [
                    'success' => true,
                    'label_id' => $labelId,
                    'message' => 'Label criado com sucesso',
                ];
            }

            // Se label já existe (409), buscar o ID existente
            if ($response->status() === 409) {
                $existingLabel = $this->findLabelByName($integration, $labelName);
                
                if ($existingLabel) {
                    return [
                        'success' => true,
                        'label_id' => $existingLabel['id'],
                        'message' => 'Label já existe',
                    ];
                }
            }

            Log::error('Failed to create Gmail label', [
                'label_name' => $labelName,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return [
                'success' => false,
                'label_id' => null,
                'message' => 'Erro ao criar label no Gmail',
            ];
        } catch (\Exception $e) {
            Log::error('Exception creating Gmail label', [
                'label_name' => $labelName,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'label_id' => null,
                'message' => 'Erro ao comunicar com Gmail API',
            ];
        }
    }

    /**
     * Lista todos os labels do usuário no Gmail
     *
     * @param Integration $integration
     * @return array
     */
    public function listLabels(Integration $integration): array
    {
        try {
            $accessToken = $integration->access_token;

            $response = Http::withToken($accessToken)
                ->get(self::GMAIL_API_BASE . '/users/me/labels');

            if ($response->successful()) {
                return $response->json('labels', []);
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Exception listing Gmail labels', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Busca label por nome
     *
     * @param Integration $integration
     * @param string $labelName
     * @return array|null
     */
    public function findLabelByName(Integration $integration, string $labelName): ?array
    {
        $labels = $this->listLabels($integration);

        foreach ($labels as $label) {
            if ($label['name'] === $labelName) {
                return $label;
            }
        }

        return null;
    }

    /**
     * Aplica label em um e-mail específico
     *
     * @param Integration $integration
     * @param string $messageId Gmail message ID (não confundir com Message-ID header)
     * @param string $labelName Nome do label
     * @return array ['success' => bool, 'message' => string]
     */
    public function applyLabel(Integration $integration, string $messageId, string $labelName): array
    {
        try {
            // Primeiro, garantir que label existe
            $labelResult = $this->createLabel($integration, $labelName);
            
            if (!$labelResult['success']) {
                return $labelResult;
            }

            $labelId = $labelResult['label_id'];
            $accessToken = $integration->access_token;

            // Aplicar label no e-mail
            $response = Http::withToken($accessToken)
                ->post(self::GMAIL_API_BASE . "/users/me/messages/{$messageId}/modify", [
                    'addLabelIds' => [$labelId],
                ]);

            // Se 401 e temos refresh_token, tentar refresh
            if ($response->status() === 401 && $this->hasRefreshToken($integration)) {
                $newToken = $this->refreshAccessToken($integration);
                
                if ($newToken) {
                    $integration->refresh();
                    
                    // Retry aplicação do label
                    $response = Http::withToken($integration->access_token)
                        ->post(self::GMAIL_API_BASE . "/users/me/messages/{$messageId}/modify", [
                            'addLabelIds' => [$labelId],
                        ]);
                }
            }

            if ($response->successful()) {
                Log::info('Gmail label applied', [
                    'message_id' => $messageId,
                    'label_name' => $labelName,
                    'label_id' => $labelId,
                ]);

                return [
                    'success' => true,
                    'message' => 'Label aplicado com sucesso',
                ];
            }

            // Se ainda 401 após refresh, pedir reconexão
            if ($response->status() === 401) {
                return [
                    'success' => false,
                    'message' => 'Token expirado. Reconecte o Gmail.',
                ];
            }

            Log::error('Failed to apply Gmail label', [
                'message_id' => $messageId,
                'label_name' => $labelName,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao aplicar label no Gmail',
            ];
        } catch (\Exception $e) {
            Log::error('Exception applying Gmail label', [
                'message_id' => $messageId,
                'label_name' => $labelName,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao comunicar com Gmail API',
            ];
        }
    }

    /**
     * Remove label de um e-mail específico
     *
     * @param Integration $integration
     * @param string $messageId
     * @param string $labelName
     * @return array ['success' => bool, 'message' => string]
     */
    public function removeLabel(Integration $integration, string $messageId, string $labelName): array
    {
        try {
            $label = $this->findLabelByName($integration, $labelName);
            
            if (!$label) {
                return [
                    'success' => false,
                    'message' => 'Label não encontrado',
                ];
            }

            $labelId = $label['id'];
            $accessToken = $integration->access_token;

            $response = Http::withToken($accessToken)
                ->post(self::GMAIL_API_BASE . "/users/me/messages/{$messageId}/modify", [
                    'removeLabelIds' => [$labelId],
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Label removido com sucesso',
                ];
            }

            return [
                'success' => false,
                'message' => 'Erro ao remover label do Gmail',
            ];
        } catch (\Exception $e) {
            Log::error('Exception removing Gmail label', [
                'message_id' => $messageId,
                'label_name' => $labelName,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao comunicar com Gmail API',
            ];
        }
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

<?php

declare(strict_types=1);

namespace App\Domain\Automations\Http\Actions;

use Illuminate\Support\Facades\Http;
use Exception;

/**
 * Executes an HTTP Request based on provided configuration.
 */
class ExecuteHttpRequestAction
{
    /**
     * Execute the HTTP request.
     *
     * @param string $url The target URL.
     * @param string $method GET, POST, PUT, DELETE, etc.
     * @param array $headers key-value pairs of headers.
     * @param string|null $body JSON string for the body.
     * @return array Standardized response ['status' => int, 'data' => mixed]
     */
    public function execute(string $url, string $method, array $headers = [], ?string $body = null): array
    {
        try {
            // Prepare Request
            $request = Http::withHeaders($this->parseHeaders($headers));

            // Execute
            $response = match (strtoupper($method)) {
                'GET' => $request->get($url),
                'POST' => $request->post($url, $this->parseBody($body)),
                'PUT' => $request->put($url, $this->parseBody($body)),
                'DELETE' => $request->delete($url, $this->parseBody($body)),
                default => throw new Exception("Method $method not supported"),
            };

            // Parse Response
            return [
                'status' => $response->status(),
                'data' => $response->json(), // Assumes JSON response for now
                'headers' => $response->headers(),
            ];

        } catch (Exception $e) {
            return [
                'status' => 500,
                'error' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Convert headers array [{'key' => '...', 'value' => '...'}] to ['key' => 'value']
     */
    private function parseHeaders(array $headers): array
    {
        $parsed = [];
        foreach ($headers as $h) {
            if (!empty($h['key'])) {
                $parsed[$h['key']] = $h['value'];
            }
        }
        return $parsed;
    }

    private function parseBody(?string $body): array
    {
        if (empty($body)) {
            return [];
        }
        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : [];
    }
}

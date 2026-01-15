<?php

declare(strict_types=1);

namespace App\Domain\AI\Controllers;

use App\Domain\AI\Services\TextAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

/**
 * Controller para API de assistente de IA
 * 
 * Endpoints para melhorar/corrigir/expandir/resumir textos
 */
final class AIAssistantController extends Controller
{
    public function __construct(
        private readonly TextAssistantService $textAssistant
    ) {}

    /**
     * Melhora texto usando IA
     *
     * POST /api/ai/improve-text
     * Body: { "text": "...", "action": "improve|correct|expand|summarize" }
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function improveText(Request $request): JsonResponse
    {
        // Validação
        $validator = Validator::make($request->all(), [
            'text' => ['required', 'string', 'min:1', 'max:10000'],
            'action' => ['required', 'string', 'in:improve,correct,expand,summarize'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dados inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        $text = $request->input('text');
        $action = $request->input('action');

        try {
            // Processa com IA
            $improvedText = $this->textAssistant->process($text, $action);

            return response()->json([
                'text' => $improvedText,
                'action' => $action,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao processar texto. Tente novamente.',
            ], 500);
        }
    }
}

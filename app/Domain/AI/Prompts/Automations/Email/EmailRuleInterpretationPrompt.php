<?php

declare(strict_types=1);

namespace App\Domain\AI\Prompts\Automations\Email;

use App\Domain\AI\Prompts\BasePrompt;

final class EmailRuleInterpretationPrompt extends BasePrompt
{
    /**
     * @param array{rule:string,event:array} $context
     */
    public function render(array $context = []): string
    {
        $rule = $context['rule'] ?? '';
        $event = $context['event'] ?? [];

        $from = $event['from'] ?? '';
        $subject = $event['subject'] ?? '';
        $body = $event['body'] ?? ($event['snippet'] ?? '');

        $jsonSchema = json_encode([
            'should_execute' => 'boolean (true to execute, false to ignore)',
            'confidence' => 'number between 0 and 1 (0.0 = unsure, 1.0 = certain)',
            'reason' => 'short explanation of why',
            'matched_sender' => 'boolean: sender aligns with rule',
            'matched_action' => 'boolean: action aligns with rule',
        ], JSON_PRETTY_PRINT);

        return <<<PROMPT
Você é um assistente que interpreta regras em linguagem natural para e-mails.

Regra do usuário:
"{$rule}"

Evento de e-mail (campos já normalizados):
- From: {$from}
- Subject: {$subject}
- Body/snippet: {$body}

Tarefa: Diga se a regra quer executar alguma ação para este e-mail.

Responda SOMENTE um JSON com este formato:
{$jsonSchema}

Campos obrigatórios:
- should_execute: true ou false
- confidence: número entre 0 e 1
- reason: texto curto
- matched_sender: true/false
- matched_action: true/false

Não adicione texto fora do JSON.
PROMPT;
    }
}

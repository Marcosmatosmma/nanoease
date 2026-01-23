<?php

declare(strict_types=1);

namespace App\Domain\AI\Prompts\Automations\Email;

use App\Domain\AI\Prompts\BasePrompt;
use Filament\Tables\Filters\QueryBuilder\Forms\Components\RuleBuilder;

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
Você é um verificador de automações de e-mail. Determine se um e-mail atende à condição especificada.

**CONDIÇÃO:**
{$rule}

**E-MAIL:**
- Remetente: {$from}
- Assunto: {$subject}
- Prévia: {$body}

---

**INSTRUÇÕES:**

A CONDIÇÃO descreve QUANDO executar a automação. Sua tarefa é verificar se o e-mail atual atende a essa condição.

**Processo:**

1. **Identifique o que buscar** na condição:
   - Palavras-chave específicas (ex: "Mercado Pago", "fatura", "urgente", "NF", "NFe")
   - Conceitos semânticos (ex: "mensagens tristes", "e-mails urgentes")
   - Remetentes/domínios específicos (ex: "@mercadopago.com.br")

2. **Busque no e-mail** (case-insensitive):
   - No **remetente** (nome e endereço)
   - No **assunto**
   - Na **prévia/corpo**

3. **Decida:**
   - ✅ **TRUE** se encontrou as palavras-chave/conceito EM QUALQUER dos campos
   - ❌ **FALSE** se NÃO encontrou

**IMPORTANTE:**
- Seja literal: se a condição pede "NF" ou "NFe", encontre exatamente essas palavras
- "NF-e" = "NFe" (são a mesma coisa)
- Busque a palavra completa, não apenas parte dela (ex: "NF" não deve dar match em "INFO")

---

**RESPOSTA:**

Retorne APENAS JSON válido:
{$jsonSchema}

**Campos:**
- `should_execute`: true/false
- `confidence`: 
  - 0.9-1.0 → match literal de palavra-chave
  - 0.7-0.89 → match contextual/semântico
  - 0.5-0.69 → match fraco/incerto
  - 0.0-0.49 → não atende
- `reason`: Explique objetivamente:
  - **Se TRUE**: "Palavra 'X' encontrada no [remetente/assunto/corpo]"
  - **Se FALSE**: "Palavras-chave 'X' não encontradas"
- `matched_sender`: true se o remetente é relevante para a condição
- `matched_action`: true se o conteúdo (assunto/corpo) é relevante

**Exemplos de `reason`:**
✅ "Palavra 'NF-e' encontrada no assunto"
✅ "Palavra 'Mercado Pago' encontrada no remetente"
✅ "Palavra 'fatura' encontrada no assunto"
✅ "Palavras 'boleto' e 'vencimento' encontradas no corpo"
❌ "Palavras-chave 'NF', 'NFe' não encontradas"
PROMPT;
    }
}

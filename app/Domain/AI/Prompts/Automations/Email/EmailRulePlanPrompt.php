<?php

declare(strict_types=1);

namespace App\Domain\AI\Prompts\Automations\Email;

use App\Domain\AI\Prompts\BasePrompt;

final class EmailRulePlanPrompt extends BasePrompt
{
    /**
     * @param array{rule:string,event:?string,action:?string,action_config:?array} $context
     */
    public function render(array $context = []): string
    {
        $rule = $context['rule'] ?? '';
        $event = $context['event'] ?? 'event not specified';
        $action = $context['action'] ?? 'action not specified';
        $actionConfig = $context['action_config'] ?? [];
        $actionConfigJson = json_encode($actionConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Extrair informações específicas do action_config
        $gmailLabel = $actionConfig['gmail_label'] ?? null;
        $forwardTo = $actionConfig['forward_to'] ?? [];
        $replySubject = $actionConfig['reply_subject'] ?? null;
        $replyBody = $actionConfig['reply_body'] ?? null;

        return <<<PROMPT
Você é um especialista em expandir regras de automação de e-mail. Transforme a regra simples do usuário em uma descrição clara e específica de QUANDO executar a ação.

## ENTRADA

**Regra do usuário**: "{$rule}"
**Ação**: {$action}
**Configuração**: {$actionConfigJson}

---

## SUA TAREFA

Expanda APENAS a **CONDIÇÃO** (quando executar). Seja específico sobre:

1. **O QUE buscar** - Extraia as palavras-chave ou conceito da regra
2. **ONDE buscar** - Especifique: remetente, assunto, corpo, domínio
3. **COMO interpretar** - Se é busca literal, semântica, por domínio, etc

**IMPORTANTE:**
- ✅ USE EXATAMENTE as configurações fornecidas (nomes de labels, destinatários, etc)
- ✅ NÃO INVENTE informações que não foram fornecidas
- ✅ Seja PRECISO na condição (palavras-chave, domínios, etc)
- ✅ Mantenha 1-2 frases objetivas
- ❌ NÃO copie a regra original sem melhorar
- ❌ NÃO seja genérico ("quando um email chegar...")

---

## EXEMPLOS

**Entrada:** "e-mail do mercado pago"
**Saída:** "Quando receber um e-mail que mencione 'Mercado Pago' ou 'mercadopago' no remetente, assunto ou corpo."

**Entrada:** "fatura, boletos, nota fiscal"
**Saída:** "Quando receber um e-mail que contenha as palavras 'fatura', 'boleto' ou 'nota fiscal' no assunto ou corpo (excluindo e-mails de compra de produtos)."

**Entrada:** "mensagens urgentes"
**Saída:** "Quando receber um e-mail que contenha a palavra 'urgente' no assunto ou que expresse necessidade de ação imediata no conteúdo."

---

Retorne APENAS a condição expandida. Sem prefixos, sem explicações extras.
PROMPT;
    }
}

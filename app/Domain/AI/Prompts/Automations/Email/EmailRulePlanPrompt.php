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
3. **COMO interpretar** - SEMPRE prefira busca por PALAVRAS INDIVIDUAIS, não frases completas

**IMPORTANTE:**
- ✅ USE EXATAMENTE as configurações fornecidas (nomes de labels, destinatários, etc)
- ✅ NÃO INVENTE informações que não foram fornecidas
- ✅ Seja PRECISO na condição (palavras-chave, domínios, etc)
- ✅ Mantenha 1-2 frases objetivas
- ✅ **SEMPRE especifique PALAVRAS-CHAVE individuais, NÃO frases completas**
- ✅ **Use "ou variações" para cobrir plural, singular, sinônimos comuns**
- ❌ NÃO copie a regra original sem melhorar
- ❌ NÃO seja genérico ("quando um email chegar...")
- ❌ **NÃO crie regras que peçam "frases completas" como 'marcação de reunião'**
- ❌ **NÃO use múltiplas palavras juntas como uma única condição**

---

## EXEMPLOS

**Entrada:** "e-mail do mercado pago"
**Saída:** "Quando receber um e-mail que mencione a palavra 'mercado' e 'pago' (ou 'mercadopago') no remetente, assunto ou corpo."

**Entrada:** "fatura, boletos, nota fiscal"
**Saída:** "Quando receber um e-mail que contenha as palavras 'fatura', 'boleto', 'nota' ou 'fiscal' no assunto ou corpo."

**Entrada:** "mensagens urgentes"
**Saída:** "Quando receber um e-mail que contenha a palavra 'urgente' (ou variações como 'urgência', 'urgent') no assunto ou corpo."

**Entrada:** "e-mail que contenham marcação de reunião"
**Saída:** "Quando receber um e-mail que contenha a palavra 'reunião' (ou variações como 'reuniao', 'meeting', 'meet') no assunto ou corpo."

**Entrada:** "confirmação de pedido"
**Saída:** "Quando receber um e-mail que contenha as palavras 'confirmação' ou 'pedido' (ou variações como 'confirmar', 'order') no assunto ou corpo."

---

## REGRA DE OURO

**SEMPRE prefira palavras individuais em vez de frases completas.**
- ❌ ERRADO: "as expressões 'marcação de reunião', 'convite para reunião'"
- ✅ CORRETO: "a palavra 'reunião' ou variações"

Retorne APENAS a condição expandida. Sem prefixos, sem explicações extras.
PROMPT;
    }
}

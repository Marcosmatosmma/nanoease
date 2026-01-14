<?php

declare(strict_types=1);

namespace App\Domain\AI\Prompts\Automations\Email;

use App\Domain\AI\Prompts\BasePrompt;

final class EmailActionExecutionPrompt extends BasePrompt
{
    /**
     * @param array{plan_rule:string,event:array,available_actions:array} $context
     */
    public function render(array $context = []): string
    {
        $planRule = $context['plan_rule'] ?? '';
        $event = $context['event'] ?? [];
        $availableActions = $context['available_actions'] ?? [];

        $from = $event['from'] ?? '';
        $subject = $event['subject'] ?? '';
        $body = $event['body'] ?? ($event['snippet'] ?? '');

        $actionsJson = json_encode($availableActions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $jsonSchema = json_encode([
            'should_execute' => 'boolean (true para executar, false para ignorar)',
            'confidence' => 'number entre 0 e 1 (0.0 = incerto, 1.0 = certeza total)',
            'matched_conditions' => 'array de strings descrevendo quais condições da regra foram atendidas',
            'action_to_execute' => 'string com o tipo da ação a executar (ex: "encaminhar", "responder", "classificar", "criar_tarefa")',
            'action_parameters' => 'object com os parâmetros necessários para a ação',
            'reasoning' => 'string explicando brevemente a decisão'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
# Contexto do Sistema MCP - Model Context Protocol

Você é um VERIFICADOR RIGOROSO de automações de e-mail. Sua função é analisar e-mails com EXTREMA PRECISÃO e LITERALIDADE.

## Regra Planejada (já otimizada para interpretação):
```
{$planRule}
```

## E-mail Recebido:
- **Remetente**: {$from}
- **Assunto**: {$subject}
- **Corpo**: {$body}

## Ações Disponíveis:
```json
{$actionsJson}
```

---

## REGRAS ANTI-ALUCINAÇÃO (CRÍTICO):

⚠️ **PROIBIDO INVENTAR INFORMAÇÕES**
- Você DEVE ser 100% LITERAL ao verificar palavras-chave ou FRASES COMPLETAS
- NUNCA diga que encontrou algo se NÃO APARECE EXATAMENTE no texto
- SEMPRE cite o trecho EXATO onde encontrou a condição
- Em caso de QUALQUER dúvida: `should_execute: false`

✅ **PADRÃO DE VERIFICAÇÃO ULTRA LITERAL:**
1. A regra pede FRASE "cartão de embarque"? Procure EXATAMENTE "cartão de embarque" (todas as palavras juntas)
2. Encontrou a FRASE COMPLETA? Cite o trecho: "encontrado em: '...cartão de embarque...'"
3. Encontrou apenas PARTE da frase (ex: só "cartão" ou só "embarque")? → `should_execute: false`
4. Encontrou palavra similar/relacionada? → `should_execute: false`
5. NÃO encontrou EXATAMENTE? → `should_execute: false` - SEM exceções

❌ **PROIBIDO - EXEMPLOS DE FALSOS POSITIVOS:**
- "Cartão: CE-946" NÃO contém "cartão de embarque" (falta "de embarque")
- "SACFiscal Automação" NÃO contém "SACFiscal" sozinho? → SIM contém, OK
- "Solicita fiscal" NÃO contém "SACFiscal" (palavras diferentes)
- "O e-mail menciona indiretamente..." → Se não está escrito EXATAMENTE, não existe
- "O contexto sugere que..." → Análise deve ser literal, não interpretativa
- "Parece relacionado a..." → Relação não é match exato
- "A palavra X está implícita..." → Implícito = NÃO EXISTE literalmente
- Aceitar sinônimos ou termos relacionados ou partes da frase → PROIBIDO

🎯 **MATCH VÁLIDO:**
- Regra: "cartão de embarque" → Corpo: "Aqui está seu cartão de embarque" ✅
- Regra: "cartão de embarque" → Corpo: "Cartão: CE-946" ❌ (falta "de embarque")
- Regra: "SACFiscal" → Corpo: "Boletim SACFiscal" ✅
- Regra: "SACFiscal" → Corpo: "SAC Fiscal" ❌ (separado, não é exatamente igual)

---

## Sua Tarefa:

1. **Verifique LITERALMENTE** se o e-mail atende às condições da regra
2. **Cite trechos exatos** que comprovem o match (ou indique que não encontrou)
3. **Decida** se deve executar (padrão: NÃO, só SIM se houver match comprovado)
4. **Retorne** APENAS um objeto JSON válido seguindo este schema:

```json
{$jsonSchema}
```

---

## Regras de Decisão (RIGOROSAS):

- **PADRÃO**: `should_execute: false` (só muda para true com EVIDÊNCIA CONCRETA)
- `should_execute: true` SOMENTE se você consegue CITAR o trecho exato do e-mail que atende à regra
- `confidence` deve ser < 0.8 se houver qualquer ambiguidade
- `matched_conditions` deve incluir os TRECHOS LITERAIS encontrados (ex: "palavra 'SACFiscal' encontrada no corpo: '...Projeto SACFiscal...'")
- `reasoning` deve explicar EXATAMENTE onde/como a condição foi atendida (ou por que não foi)
- Se a regra menciona palavras-chave específicas, você DEVE encontrá-las LITERALMENTE

---

## Exemplos de Verificação Correta:

**Regra**: "E-mail deve conter a palavra 'urgente'"
**Corpo**: "Prezado, preciso de ajuda com o sistema."
**Decisão**: `should_execute: false` - palavra 'urgente' NÃO encontrada
**Reasoning**: "A palavra 'urgente' não aparece no assunto nem no corpo do e-mail."

**Regra**: "E-mail deve conter a palavra 'urgente'"
**Corpo**: "URGENTE: Sistema fora do ar!"
**Decisão**: `should_execute: true` - palavra encontrada
**Matched conditions**: ["palavra 'urgente' encontrada no corpo: 'URGENTE: Sistema fora do ar!'"]
**Reasoning**: "Palavra 'urgente' encontrada literalmente no início do corpo."

---

## Exemplos de action_parameters:

**Para "encaminhar":**
```json
{
  "forward_to": ["destino@example.com"],
  "preserve_original": true,
  "add_note": "Encaminhado automaticamente"
}
```

**Para "responder":**
```json
{
  "reply_text": "Obrigado pelo contato. Recebemos sua mensagem.",
  "reply_to": "remetente original"
}
```

**Para "classificar":**
```json
{
  "category": "urgente",
  "tags": ["cliente", "suporte"]
}
```

---

**LEMBRE-SE**: 
- SER LITERAL é mais importante que ser útil
- Em caso de dúvida: `should_execute: false`
- NUNCA invente que encontrou algo que não está escrito

**IMPORTANTE**: Retorne SOMENTE o JSON. Sem texto adicional antes ou depois.
PROMPT;
    }
}

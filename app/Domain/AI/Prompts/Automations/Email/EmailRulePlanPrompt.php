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

        return <<<PROMPT
Você é um PLANEJADOR DE AUTOMAÇÕES especializado. Sua missão é transformar regras em linguagem natural em planos de execução PRECISOS e EXECUTÁVEIS.

## CONTEXTO DA AUTOMAÇÃO

**Evento detectado**: {$event}
**Regra original do usuário**: "{$rule}"
**Ação a executar**: {$action}
**Configuração da ação**:
```json
{$actionConfigJson}
```

---

## SUA TAREFA

Crie um PLANO OPERACIONAL que descreva:

1. **CONDIÇÃO EXATA** - Quando/em que situação a automação deve ser executada
2. **CRITÉRIOS ESPECÍFICOS** - Quais características do e-mail devem ser verificadas
3. **AÇÃO PRECISA** - O que exatamente será feito (com destinatários, configurações, etc)

---

## REGRAS OBRIGATÓRIAS

✅ **SIM - Faça isso:**
- Seja ESPECÍFICO e DETALHADO sobre a condição
- Inclua os CRITÉRIOS SEMÂNTICOS exatos (palavras-chave, temas, intenções)
- Mencione EXPLICITAMENTE os destinatários/configurações quando aplicável
- Use tom OPERACIONAL (objetivo, claro, sem ambiguidade)
- Mantenha entre 2-3 frases concisas

❌ **NÃO - Evite:**
- Copiar a regra original sem melhorar
- Ser genérico ou vago ("quando um email chegar...")
- Omitir detalhes importantes da condição
- Usar formato JSON, bullets ou markdown
- Inventar ações que não existem

---

## EXEMPLOS DE BONS PLANOS

**Ruim** (genérico):
"Quando um email for recebido, encaminhe para usuario@exemplo.com."

**Bom** (específico):
"Quando receber um e-mail cujo CONTEÚDO mencione explicitamente temas de 'educação', 'crescimento pessoal' ou 'ensino', encaminhe-o para usuario@exemplo.com preservando o conteúdo original."

**Ruim** (vago):
"Se for do domínio empresa.com, encaminhar."

**Bom** (preciso):
"Quando o remetente pertencer ao domínio @empresa.com (qualquer usuário), encaminhe automaticamente para equipe@destino.com."

---

## RETORNE APENAS O PLANO TEXTUAL

Não inclua prefixos, explicações ou formatação especial. Apenas o plano operacional em texto corrido.
PROMPT;
    }
}

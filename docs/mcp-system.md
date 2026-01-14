# Sistema MCP - Model Context Protocol para Automações

## 📋 Visão Geral

O sistema MCP (Model Context Protocol) é uma arquitetura inteligente que permite ao Prism (LLM) interpretar regras em linguagem natural (`plan_rule_text`) e executar ações de forma estruturada e previsível.

## 🏗️ Arquitetura

### Fluxo de Execução

```
Evento (E-mail) 
    ↓
[ActionDecisionEngine]
    ↓ (consulta Prism via ActionExecutionPrompt)
Decisão Estruturada (JSON)
    ↓
[AutomationExecutor]
    ↓
Execução da Ação
    ↓
Resultado + Log
```

## 📂 Componentes Criados

### 1. `ActionExecutionPrompt.php`
**Localização:** `app/Domain/AI/Prompts/ActionExecutionPrompt.php`

Prompt estruturado que guia o Prism a retornar uma decisão em formato JSON padronizado.

**Saída esperada:**
```json
{
  "should_execute": true,
  "confidence": 0.95,
  "matched_conditions": ["remetente corresponde", "assunto relevante"],
  "action_to_execute": "encaminhar",
  "action_parameters": {
    "forward_to": ["destino@example.com"],
    "add_note": "Encaminhado automaticamente"
  },
  "reasoning": "E-mail de marcos.araujo@supleti.com atende critério da regra"
}
```

### 2. `ActionDecisionEngine.php`
**Localização:** `app/Domain/AI/Services/ActionDecisionEngine.php`

Motor de decisão que:
- Envia prompt estruturado ao Prism
- Parseia a resposta JSON
- Normaliza valores (confidence, etc)
- Retorna decisão estruturada
- Trata erros com fallback

**Método principal:**
```php
public function decide(
    string $planRuleText,    // Regra otimizada
    array $event,            // E-mail normalizado
    array $availableActions  // Ações disponíveis
): array
```

### 3. `AutomationExecutor.php`
**Localização:** `app/Domain/Automations/Services/AutomationExecutor.php`

Executor central que:
- Verifica status da automação
- Consulta o ActionDecisionEngine
- Executa a ação decidida pelo Prism
- Retorna resultado estruturado

**Ações implementadas:**
- ✅ `encaminhar` - Encaminha e-mail
- ✅ `responder` - Responde automaticamente
- 🚧 `classificar` - Organiza/categoriza (placeholder)
- 🚧 `criar_tarefa` - Cria tarefa interna (placeholder)

## 🧪 Testando o Sistema MCP

### Command de Teste Atualizado

```bash
php artisan automations:test {automation_id} [opções]
```

### Opções Disponíveis

| Opção | Descrição |
|-------|-----------|
| `--use-mcp` | **Ativa o motor MCP** (novo sistema inteligente) |
| `--execute` | Executa de fato (envia e-mails) |
| `--search="texto"` | Busca e-mails no Gmail |
| `--limit=20` | Limite de e-mails na busca |
| `--from` | Override do remetente |
| `--subject` | Override do assunto |
| `--body` | Override do corpo |

### Exemplos de Uso

#### 1. Teste Simples (modo antigo)
```bash
php artisan automations:test 1 \
  --from="marcos.araujo@supleti.com" \
  --subject="Teste de automação"
```

#### 2. Teste com MCP (novo motor inteligente)
```bash
php artisan automations:test 1 \
  --use-mcp \
  --from="marcos.araujo@supleti.com" \
  --subject="Teste de automação"
```

#### 3. Buscar e Analisar E-mails Reais (Gmail)
```bash
php artisan automations:test 1 \
  --use-mcp \
  --search="from:marcos.araujo@supleti.com" \
  --limit=10
```

#### 4. Buscar, Analisar e Executar
```bash
php artisan automations:test 1 \
  --use-mcp \
  --search="from:marcos.araujo@supleti.com" \
  --limit=5 \
  --execute
```

### Saída Comparativa

#### Modo Antigo (sem --use-mcp)
```
+------+---------------------------+------------------+----------+-----------+
| ID   | From                      | Assunto          | Decisão  | Confiança |
+------+---------------------------+------------------+----------+-----------+
| 123  | marcos.araujo@supleti.com | Teste automação  | executar | 0.85      |
+------+---------------------------+------------------+----------+-----------+
```

#### Modo MCP (com --use-mcp)
```
🧠 Modo MCP ativado - usando ActionDecisionEngine

+------+---------------------------+------------------+----------+-----------+------------+----------------------------------+
| ID   | From                      | Assunto          | Decisão  | Confiança | Ação       | Raciocínio                       |
+------+---------------------------+------------------+----------+-----------+------------+----------------------------------+
| 123  | marcos.araujo@supleti.com | Teste automação  | executar | 0.95      | encaminhar | Remetente corresponde à regra... |
+------+---------------------------+------------------+----------+-----------+------------+----------------------------------+
```

## 📊 Estrutura de Dados

### Event (E-mail Normalizado)
```php
[
    'from' => 'remetente@example.com',
    'subject' => 'Assunto do e-mail',
    'body' => 'Corpo completo...',
    'snippet' => 'Prévia do corpo...',
]
```

### Available Actions
```php
[
    [
        'type' => 'encaminhar',
        'position' => 1,
        'config' => [
            'forward_to' => ['destino@example.com'],
        ],
    ],
]
```

### Decision Result
```php
[
    'should_execute' => true,
    'confidence' => 0.95,
    'matched_conditions' => ['condição 1', 'condição 2'],
    'action_to_execute' => 'encaminhar',
    'action_parameters' => [
        'forward_to' => ['destino@example.com'],
        'add_note' => 'Nota opcional',
    ],
    'reasoning' => 'Explicação da decisão',
    'raw_response' => '{"should_execute":true,...}',
]
```

### Execution Result
```php
[
    'status' => 'executed', // 'executed', 'ignored', 'error'
    'message' => 'E-mail encaminhado com sucesso',
    'context' => [
        'recipients' => ['destino@example.com'],
        'decision' => [
            'confidence' => 0.95,
            'matched_conditions' => [...],
            'reasoning' => '...',
        ],
        'provider' => 'mailtrap',
    ],
]
```

## 🎯 Próximos Passos

### Para Produção
1. Integrar `AutomationExecutor` no fluxo real de recebimento de e-mails
2. Implementar as ações `classificar` e `criar_tarefa`
3. Adicionar registro de execuções (tabela `automation_executions`)
4. Criar dashboard para visualizar decisões e logs

### Melhorias no MCP
1. Cache de decisões para e-mails similares
2. Aprendizado com feedback (thumbs up/down nas execuções)
3. Múltiplas ações em sequência
4. Condições compostas (AND/OR) - mantendo invisível ao usuário

## ⚠️ Importante

- O Prism **nunca executa** ações diretamente, apenas **interpreta e decide**
- A execução final sempre passa pelo `AutomationExecutor`
- Todas as decisões são registradas para auditoria
- O usuário pode revisar decisões antes de ativar a automação (modo simulação)

## 🔒 Segurança

- Validação de destinatários antes de enviar e-mails
- Limite de confiança mínimo configurável
- Logs detalhados de todas as execuções
- Modo sandbox/dry-run disponível

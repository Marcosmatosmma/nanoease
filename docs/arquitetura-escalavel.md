# 🏗️ Arquitetura Escalável - Sistema de Automações

## 📋 Visão Geral

Esta é a arquitetura proposta para tornar o sistema de automações verdadeiramente escalável, permitindo adicionar novos tipos de eventos (webhooks, tarefas, CRM, etc) sem acoplar tudo ao domínio de e-mail.

---

## 🎯 Problema Atual

A implementação inicial estava muito acoplada ao evento `email_received`:

```
❌ Estrutura Antiga (não escalável)
app/Domain/AI/
├── Prompts/
│   ├── ActionExecutionPrompt.php          ← específico de e-mail
│   ├── RuleInterpretationPrompt.php       ← específico de e-mail
│   └── RulePlanPrompt.php                 ← específico de e-mail
└── Services/
    ├── ActionDecisionEngine.php           ← hardcoded para e-mail
    └── AutomationExecutor.php             ← hardcoded para e-mail
```

**Limitações:**
- Impossível adicionar novos tipos de evento sem modificar código existente
- Prompts misturados sem organização por contexto
- Executores não são plugáveis
- Quebra o Open/Closed Principle (SOLID)

---

## ✅ Nova Estrutura Escalável

```
app/Domain/AI/
├── Contracts/
│   └── DecisionEngineInterface.php        ← interface comum
│
├── Prompts/
│   ├── BasePrompt.php
│   │
│   ├── Automations/                       ← namespace por domínio
│   │   ├── Email/
│   │   │   ├── EmailActionExecutionPrompt.php
│   │   │   ├── EmailRuleInterpretationPrompt.php
│   │   │   └── EmailRulePlanPrompt.php
│   │   │
│   │   ├── Webhook/                       ← futuro
│   │   │   ├── WebhookActionExecutionPrompt.php
│   │   │   └── WebhookRuleInterpretationPrompt.php
│   │   │
│   │   ├── Task/                          ← futuro
│   │   │   ├── TaskActionExecutionPrompt.php
│   │   │   └── TaskRuleInterpretationPrompt.php
│   │   │
│   │   └── CRM/                           ← futuro
│   │       ├── CRMActionExecutionPrompt.php
│   │       └── CRMRuleInterpretationPrompt.php
│   │
│   └── Chat/                              ← outro domínio (já existe)
│       └── ChatPrompt.php
│
└── Services/
    ├── PrismClient.php                    ← genérico
    │
    └── Automations/
        ├── EmailDecisionEngine.php        ← implementa DecisionEngineInterface
        ├── WebhookDecisionEngine.php      ← futuro
        ├── TaskDecisionEngine.php         ← futuro
        └── CRMDecisionEngine.php          ← futuro
```

```
app/Domain/Automations/
├── Contracts/
│   └── AutomationExecutorInterface.php    ← interface comum
│
├── Services/
│   ├── AutomationOrchestrator.php         ← factory/resolver
│   │
│   └── Executors/
│       ├── EmailAutomationExecutor.php    ← implementa AutomationExecutorInterface
│       ├── WebhookAutomationExecutor.php  ← futuro
│       ├── TaskAutomationExecutor.php     ← futuro
│       └── CRMAutomationExecutor.php      ← futuro
│
└── Actions/
    └── ... (ações específicas)
```

---

## 🔌 Interfaces (Contracts)

### 1. DecisionEngineInterface

```php
namespace App\Domain\AI\Contracts;

interface DecisionEngineInterface
{
    /**
     * Decide se deve executar e qual ação tomar.
     */
    public function decide(
        string $planRuleText,
        array $event,
        array $availableActions
    ): array;

    /**
     * Tipo de evento que este engine suporta.
     */
    public function getEventType(): string;
}
```

**Implementações:**
- `EmailDecisionEngine` → `getEventType()` retorna `'email_received'`
- `WebhookDecisionEngine` → `getEventType()` retorna `'webhook_received'`
- `TaskDecisionEngine` → `getEventType()` retorna `'task_created'`

### 2. AutomationExecutorInterface

```php
namespace App\Domain\Automations\Contracts;

interface AutomationExecutorInterface
{
    /**
     * Executa a automação.
     */
    public function execute(Automation $automation, array $event): array;

    /**
     * Verifica se suporta o tipo de evento.
     */
    public function supports(string $eventType): bool;
}
```

**Implementações:**
- `EmailAutomationExecutor` → `supports('email_received')`
- `WebhookAutomationExecutor` → `supports('webhook_received')`
- `TaskAutomationExecutor` → `supports('task_created')`

---

## 🎛️ Orquestrador (Factory Pattern)

```php
namespace App\Domain\Automations\Services;

use App\Domain\Automations\Contracts\AutomationExecutorInterface;

final class AutomationOrchestrator
{
    /**
     * @param array<AutomationExecutorInterface> $executors
     */
    public function __construct(
        private readonly array $executors = []
    ) {}

    /**
     * Resolve o executor apropriado para o tipo de evento.
     */
    public function execute(Automation $automation, array $event): array
    {
        $eventType = $automation->event;

        foreach ($this->executors as $executor) {
            if ($executor->supports($eventType)) {
                return $executor->execute($automation, $event);
            }
        }

        throw new \RuntimeException(
            "Nenhum executor disponível para evento: {$eventType}"
        );
    }
}
```

### Registro no Service Provider

```php
// app/Providers/AppServiceProvider.php

use App\Domain\Automations\Services\AutomationOrchestrator;
use App\Domain\Automations\Services\Executors\EmailAutomationExecutor;
use App\Domain\Automations\Services\Executors\WebhookAutomationExecutor;

$this->app->singleton(AutomationOrchestrator::class, function ($app) {
    return new AutomationOrchestrator([
        $app->make(EmailAutomationExecutor::class),
        $app->make(WebhookAutomationExecutor::class),
        // adicionar novos executores aqui
    ]);
});
```

---

## 📦 Exemplo: Adicionar Novo Tipo de Evento (Webhook)

### 1. Criar Prompts Específicos

```php
// app/Domain/AI/Prompts/Automations/Webhook/WebhookActionExecutionPrompt.php

namespace App\Domain\AI\Prompts\Automations\Webhook;

use App\Domain\AI\Prompts\BasePrompt;

final class WebhookActionExecutionPrompt extends BasePrompt
{
    public function render(array $context = []): string
    {
        $planRule = $context['plan_rule'] ?? '';
        $event = $context['event'] ?? [];

        $url = $event['url'] ?? '';
        $method = $event['method'] ?? 'POST';
        $payload = json_encode($event['payload'] ?? [], JSON_PRETTY_PRINT);

        return <<<PROMPT
# Sistema MCP - Automação de Webhooks

Você analisa webhooks recebidos e decide quais ações executar.

## Regra:
{$planRule}

## Webhook Recebido:
- URL: {$url}
- Método: {$method}
- Payload:
```json
{$payload}
```

## Ações Disponíveis:
- notificar: Envia notificação
- processar_pedido: Processa pedido
- atualizar_crm: Atualiza CRM

Retorne JSON com:
- should_execute: boolean
- confidence: 0-1
- action_to_execute: string
- action_parameters: object
- reasoning: string
PROMPT;
    }
}
```

### 2. Criar Decision Engine

```php
// app/Domain/AI/Services/Automations/WebhookDecisionEngine.php

namespace App\Domain\AI\Services\Automations;

use App\Domain\AI\Contracts\DecisionEngineInterface;
use App\Domain\AI\Prompts\Automations\Webhook\WebhookActionExecutionPrompt;
use App\Domain\AI\Services\PrismClient;

final class WebhookDecisionEngine implements DecisionEngineInterface
{
    public function __construct(
        private readonly PrismClient $client,
        private readonly WebhookActionExecutionPrompt $prompt,
    ) {}

    public function decide(string $planRuleText, array $event, array $availableActions): array
    {
        $promptText = $this->prompt->render([
            'plan_rule' => $planRuleText,
            'event' => $event,
            'available_actions' => $availableActions,
        ]);

        $raw = $this->client->ask($promptText);
        // ... parsing e validação
        return $decoded;
    }

    public function getEventType(): string
    {
        return 'webhook_received';
    }
}
```

### 3. Criar Executor

```php
// app/Domain/Automations/Services/Executors/WebhookAutomationExecutor.php

namespace App\Domain\Automations\Services\Executors;

use App\Domain\Automations\Contracts\AutomationExecutorInterface;
use App\Domain\AI\Services\Automations\WebhookDecisionEngine;

final class WebhookAutomationExecutor implements AutomationExecutorInterface
{
    public function __construct(
        private readonly WebhookDecisionEngine $decisionEngine,
        // outros serviços necessários
    ) {}

    public function execute(Automation $automation, array $event): array
    {
        $decision = $this->decisionEngine->decide(
            $automation->plan_rule_text,
            $event,
            $automation->actions->toArray()
        );

        if (!$decision['should_execute']) {
            return ['status' => 'ignored', ...];
        }

        // executar ação baseado em $decision['action_to_execute']
        return match ($decision['action_to_execute']) {
            'notificar' => $this->executeNotify($event, $decision['action_parameters']),
            'processar_pedido' => $this->executeOrder($event, $decision['action_parameters']),
            default => ['status' => 'error', 'message' => 'Ação não implementada']
        };
    }

    public function supports(string $eventType): bool
    {
        return $eventType === 'webhook_received';
    }

    private function executeNotify(array $event, array $params): array
    {
        // lógica de notificação
    }

    private function executeOrder(array $event, array $params): array
    {
        // lógica de processamento
    }
}
```

### 4. Registrar no Container

```php
// app/Providers/AppServiceProvider.php

$this->app->singleton(AutomationOrchestrator::class, function ($app) {
    return new AutomationOrchestrator([
        $app->make(EmailAutomationExecutor::class),
        $app->make(WebhookAutomationExecutor::class),  // ← novo!
    ]);
});
```

**Pronto!** Novo tipo de evento adicionado sem modificar código existente.

---

## 🎨 Benefícios da Nova Arquitetura

### 1. **Escalabilidade**
Adicionar novos tipos de evento é trivial:
- Criar pasta em `Prompts/Automations/NomeEvento/`
- Criar `NomeEventoDecisionEngine`
- Criar `NomeEventoAutomationExecutor`
- Registrar no `AutomationOrchestrator`

### 2. **Organização**
Cada tipo de evento tem seus próprios prompts e lógica isolados.

### 3. **Testabilidade**
Cada componente pode ser testado independentemente.

### 4. **SOLID Principles**
- **Single Responsibility**: Cada executor cuida de um tipo de evento
- **Open/Closed**: Aberto para extensão, fechado para modificação
- **Liskov Substitution**: Todos os executores implementam a mesma interface
- **Interface Segregation**: Interfaces pequenas e focadas
- **Dependency Inversion**: Depende de interfaces, não de implementações concretas

### 5. **Manutenibilidade**
Código de e-mail não mistura com código de webhook não mistura com código de tarefas.

---

## 🛠️ Plano de Migração

### Fase 1: Refatorar Email (atual)
1. Mover prompts para `Prompts/Automations/Email/`
2. Renomear `ActionDecisionEngine` → `EmailDecisionEngine`
3. Implementar `DecisionEngineInterface`
4. Renomear `AutomationExecutor` → `EmailAutomationExecutor`
5. Implementar `AutomationExecutorInterface`

### Fase 2: Criar Orquestrador
1. Criar `AutomationOrchestrator`
2. Registrar `EmailAutomationExecutor`
3. Atualizar `RunAutomationTest` para usar orquestrador

### Fase 3: Adicionar Novos Tipos
1. Webhook
2. Task
3. CRM
4. Outros conforme necessário

---

## 📁 Estrutura Final Completa

```
app/Domain/
├── AI/
│   ├── Contracts/
│   │   └── DecisionEngineInterface.php
│   │
│   ├── Prompts/
│   │   ├── BasePrompt.php
│   │   │
│   │   ├── Automations/
│   │   │   ├── Email/
│   │   │   │   ├── EmailActionExecutionPrompt.php
│   │   │   │   ├── EmailRuleInterpretationPrompt.php
│   │   │   │   └── EmailRulePlanPrompt.php
│   │   │   │
│   │   │   ├── Webhook/
│   │   │   │   ├── WebhookActionExecutionPrompt.php
│   │   │   │   └── WebhookRuleInterpretationPrompt.php
│   │   │   │
│   │   │   ├── Task/
│   │   │   │   └── ...
│   │   │   │
│   │   │   └── CRM/
│   │   │       └── ...
│   │   │
│   │   └── Chat/
│   │       └── ChatPrompt.php
│   │
│   └── Services/
│       ├── PrismClient.php
│       ├── EmailRulePlanner.php
│       ├── RuleInterpreter.php
│       │
│       └── Automations/
│           ├── EmailDecisionEngine.php
│           ├── WebhookDecisionEngine.php
│           ├── TaskDecisionEngine.php
│           └── CRMDecisionEngine.php
│
└── Automations/
    ├── Contracts/
    │   └── AutomationExecutorInterface.php
    │
    ├── Services/
    │   ├── AutomationOrchestrator.php
    │   │
    │   └── Executors/
    │       ├── EmailAutomationExecutor.php
    │       ├── WebhookAutomationExecutor.php
    │       ├── TaskAutomationExecutor.php
    │       └── CRMAutomationExecutor.php
    │
    ├── Actions/
    │   └── ...
    │
    ├── Models/
    │   ├── Automation.php
    │   └── AutomationAction.php
    │
    └── Controllers/
        └── ...
```

---

## 🎯 Conclusão

Esta arquitetura permite que o sistema cresça de forma sustentável:

✅ **Modular**: Cada tipo de evento é independente  
✅ **Escalável**: Adicionar novos eventos é trivial  
✅ **Manutenível**: Código organizado por contexto  
✅ **Testável**: Componentes desacoplados  
✅ **SOLID**: Segue princípios de design  

**Próximo Passo**: Executar a Fase 1 (refatorar e-mail para nova estrutura).

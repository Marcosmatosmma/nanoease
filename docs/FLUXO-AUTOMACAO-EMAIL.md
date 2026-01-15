# 📧 Fluxo Completo: Automação de E-mails

Este documento explica **passo a passo** como funciona o sistema de automações de e-mail no NanoEase, desde o cadastro de uma ação até a execução em tempo real.

---

## 📋 Índice

1. [Visão Geral](#-visão-geral)
2. [Arquitetura e Componentes](#-arquitetura-e-componentes)
3. [Fluxo Completo Passo a Passo](#-fluxo-completo-passo-a-passo)
4. [Banco de Dados e Migrations](#-banco-de-dados-e-migrations)
5. [Watcher: Como Funciona](#-watcher-como-funciona)
6. [Como Criar Novas Automações](#-como-criar-novas-automações)
7. [Troubleshooting](#-troubleshooting)

---

## 🎯 Visão Geral

O sistema de automações permite que usuários criem **regras inteligentes** para processar e-mails automaticamente. O fluxo possui 3 grandes etapas:

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│  1. CADASTRO    │────▶│  2. OBSERVAÇÃO   │────▶│  3. EXECUÇÃO    │
│  (Frontend+API) │     │  (Watcher)       │     │  (Executor)     │
└─────────────────┘     └──────────────────┘     └─────────────────┘
```

### Por que foi construído assim?

- **Separação de responsabilidades**: Frontend cuida da UX, Backend processa lógica, Watcher observa continuamente
- **Prevenção de duplicação**: Sistema usa `Message-ID` + unique constraint + flag `processing`
- **Decisão inteligente**: IA (LLM) analisa e-mails quando `uses_ai=true`, caso contrário usa match direto
- **Escalável**: Estrutura modular permite adicionar novos eventos, triggers e ações facilmente

---

## 🏗️ Arquitetura e Componentes

### Camadas do Sistema

```
Frontend (Vue.js/Inertia)
    │
    ├─ EmailReceived.vue ──────────┐
    │                              │
Backend (Laravel)                  │
    │                              ▼
    ├─ Routes                  AutomationController
    │   └─ webAutomations.php      │
    │                              │
    ├─ Controllers                 ▼
    │   └─ AutomationController ───┴─▶ StoreEmailAutomationAction
    │                                      │
    ├─ Actions                             │
    │   ├─ StoreEmailAutomationAction ◀────┘
    │   └─ SimulateEmailAutomationAction
    │
    ├─ Services
    │   ├─ EmailAutomationExecutor ──▶ Executa ações
    │   ├─ GmailMessageFetcher ──────▶ Busca e-mails do Gmail
    │   └─ ActionDecisionEngine ─────▶ Decide com IA se deve executar
    │
    ├─ Models
    │   ├─ Automation ───────────────▶ Automação principal
    │   ├─ AutomationEvent ──────────▶ Catálogo de eventos (email_received)
    │   ├─ AutomationTriggerType ────▶ Tipos de trigger (sender_exact, etc)
    │   └─ AutomationAction ─────────▶ Ações a executar (encaminhar, etc)
    │
    └─ Console Commands
        └─ WatchEmailAutomations ────▶ Loop contínuo observando e-mails
```

### Arquivos Principais

| Arquivo | Responsabilidade |
|---------|-----------------|
| `routes/web.php` | Carrega `webAutomations.php` |
| `app/Domain/Automations/Routes/webAutomations.php` | Define rotas de automação |
| `app/Domain/Automations/Controllers/AutomationController.php` | Controla requisições HTTP |
| `app/Domain/Automations/Actions/StoreEmailAutomationAction.php` | Cria/atualiza automação |
| `app/Domain/Automations/Services/Executors/EmailAutomationExecutor.php` | Executa automação |
| `app/Domain/AI/Services/Automations/ActionDecisionEngine.php` | Decisão via IA |
| `app/Domain/Integrations/Services/GmailMessageFetcher.php` | Busca e-mails do Gmail |
| `app/Console/Commands/WatchEmailAutomations.php` | Observador contínuo |
| `resources/js/Pages/Automations/EmailReceived.vue` | Interface do usuário |

---

## 🔄 Fluxo Completo Passo a Passo

### **ETAPA 1: Cadastro da Automação**

#### 1.1 Frontend (Usuário preenche formulário)

**Arquivo:** `resources/js/Pages/Automations/EmailReceived.vue`

Usuário define:
- **Evento**: E-mail recebido (fixo)
- **Tipo de Condição** (`trigger_type_id`): 
  - `sender_exact`: Remetente exato
  - `sender_domain`: Domínio do remetente
  - `subject_contains`: Assunto contém
  - `content_semantic`: Contexto semântico (usa IA)
- **Condição** (`rule`): Texto da regra (ex: `financeiro@empresa.com`)
- **Ação** (`action_type`): 
  - `encaminhar`: Encaminhar e-mail
  - `responder`: Responder automaticamente
  - `organizar`: Mover para pasta
  - `tarefa`: Criar tarefa
- **Configuração da Ação** (`action_config`): JSON com parâmetros (ex: `{ forward_to: ['email@dest.com'] }`)

#### 1.2 Rota de Salvamento

**Arquivo:** `app/Domain/Automations/Routes/webAutomations.php`

```php
Route::post(
    '/automations/email-received/{automation?}',
    [AutomationController::class, 'storeEmailReceived']
)->name('automations.email-received.store');
```

- **Método**: POST
- **Endpoint**: `/automations/email-received` (criação) ou `/automations/email-received/{automation}` (edição)
- **Controller**: `AutomationController::storeEmailReceived()`

#### 1.3 Controller Processa Request

**Arquivo:** `app/Domain/Automations/Controllers/AutomationController.php`

```php
public function storeEmailReceived(
    StoreEmailAutomationRequest $request,
    ListUserIntegrationsAction $listUserIntegrationsAction,
    StoreEmailAutomationAction $storeEmailAutomationAction,
): RedirectResponse {
    $user = Auth::user();
    
    // Valida se Gmail está conectado
    $integrations = $listUserIntegrationsAction->handle($user);
    $gmail = $integrations->get('gmail');
    
    if (!$gmail || $gmail->status !== 'connected') {
        return Redirect::route('integrations.index')
            ->with('error', 'Conecte o Gmail antes de criar a automação.');
    }
    
    // Delega para Action
    $storeEmailAutomationAction->handle(
        user: $user,
        triggerTypeId: $request->validated('trigger_type_id'),
        ruleText: $request->validated('rule'),
        actionType: $request->validated('action_type'),
        actionConfig: $request->validated('action_config') ?? [],
        integrationId: $gmail->id,
        automationId: $request->route('automation'),
    );
    
    return Redirect::route('automations.index')
        ->with('success', 'Automação salva como rascunho.');
}
```

#### 1.4 Action Cria/Atualiza no Banco

**Arquivo:** `app/Domain/Automations/Actions/StoreEmailAutomationAction.php`

```php
public function handle(
    User $user,
    int $triggerTypeId,
    string $ruleText,
    string $actionType,
    array $actionConfig,
    int $integrationId,
    ?int $automationId = null,
): void {
    DB::beginTransaction();
    
    // 1. Busca automation_event_id a partir do trigger_type
    $triggerType = AutomationTriggerType::findOrFail($triggerTypeId);
    $eventId = $triggerType->automation_event_id;
    
    // 2. Gera plan_rule_text otimizado (se usa IA)
    $planRuleText = null;
    if ($triggerType->uses_ai) {
        try {
            $planRuleText = $this->emailRulePlanner->plan($ruleText, $actionType, $actionConfig);
        } catch (\Exception $e) {
            Log::warning('EmailRulePlanner falhou', ['error' => $e->getMessage()]);
            $planRuleText = $ruleText; // Fallback
        }
    }
    
    // 3. Cria ou atualiza Automation
    $automation = Automation::updateOrCreate(
        ['id' => $automationId],
        [
            'user_id' => $user->id,
            'integration_id' => $integrationId,
            'automation_event_id' => $eventId,
            'trigger_type_id' => $triggerTypeId,
            'rule_text' => $ruleText,
            'plan_rule_text' => $planRuleText,
            'status' => Automation::STATUS_DRAFT,
        ]
    );
    
    // 4. Cria ou atualiza AutomationAction
    AutomationAction::updateOrCreate(
        ['automation_id' => $automation->id, 'position' => 1],
        [
            'type' => $actionType,
            'config' => $actionConfig,
        ]
    );
    
    DB::commit();
}
```

**O que acontece:**
1. Valida `trigger_type_id` e busca `automation_event_id`
2. Se `uses_ai=true`, gera `plan_rule_text` otimizado via LLM
3. Cria registro em `automations` (status `draft`)
4. Cria registro em `automation_actions` (type, config, position)

---

### **ETAPA 2: Observação Contínua (Watcher)**

#### 2.1 Comando Artisan

**Arquivo:** `app/Console/Commands/WatchEmailAutomations.php`

```bash
php artisan automations:watch --interval=30
```

**Flags:**
- `--interval=30`: Intervalo entre verificações (segundos, mínimo 10)
- `--force`: Ignora lock e roda mesmo se outra instância estiver ativa

#### 2.2 Loop Principal

```php
public function handle(
    GmailMessageFetcher $fetcher,
    EmailAutomationExecutor $executor,
    ActionDecisionEngine $decisionEngine,
): int {
    // 🔒 Lock para evitar múltiplas instâncias
    if (!$this->option('force') && !Cache::add(self::LOCK_KEY, true, self::LOCK_TTL)) {
        $this->error('❌ Já existe uma instância do watcher rodando!');
        return self::FAILURE;
    }
    
    $interval = max(10, (int) $this->option('interval'));
    $lastCheck = now()->subMinutes(5); // Busca últimos 5min na primeira iteração
    
    while (!$this->shouldStop) {
        // Renova lock a cada iteração
        Cache::put(self::LOCK_KEY, true, self::LOCK_TTL);
        
        // 1. Busca automações ativas com Gmail conectado
        $automations = Automation::query()
            ->with(['actions', 'integration', 'triggerType', 'event'])
            ->whereHas('integration', fn($q) => $q->where('provider', 'gmail'))
            ->get();
        
        foreach ($automations as $automation) {
            // 2. Busca e-mails novos (após $lastCheck)
            $timestamp = $lastCheck->timestamp;
            $query = "after:{$timestamp} -in:sent -in:drafts";
            
            $result = $fetcher->fetch($automation->integration, $query, 20);
            
            foreach ($result['messages'] as $msg) {
                // 3. Verifica se já foi processado
                $alreadyProcessed = DB::table('automation_executions')
                    ->where('automation_id', $automation->id)
                    ->where('email_id', $msg['message_id'])
                    ->exists();
                
                if ($alreadyProcessed) {
                    continue; // Pula e-mails já processados
                }
                
                // 4. Prepara evento
                $event = [
                    'from' => $msg['from'],
                    'subject' => $msg['subject'],
                    'body' => $msg['body'],
                ];
                
                // 5. Decisão: IA ou match direto
                if ($automation->triggerType->uses_ai) {
                    $planRule = $automation->plan_rule_text ?: $automation->rule_text;
                    $decision = $decisionEngine->decide($planRule, $event, $availableActions);
                    $shouldExecute = $decision['should_execute'];
                    $confidence = $decision['confidence'];
                    $reasoning = $decision['reasoning'];
                } else {
                    $shouldExecute = $this->matchDirect($automation, $event);
                    $confidence = $shouldExecute ? 1.0 : 0.0;
                    $reasoning = 'Match direto';
                }
                
                // 6. 🔒 MARCA COMO PROCESSADO ANTES (evita duplicação)
                DB::table('automation_executions')->insert([
                    'automation_id' => $automation->id,
                    'email_id' => $msg['message_id'],
                    'email_subject' => $msg['subject'],
                    'email_from' => $msg['from'],
                    'status' => 'processing',
                    'reasoning' => $reasoning,
                    'confidence' => $confidence,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                // 7. Executa ação se necessário
                if ($shouldExecute) {
                    $execResult = $executor->execute($automation, $event);
                    $status = $execResult['status'] === 'executed' ? 'success' : 'failed';
                    
                    // Atualiza status final
                    DB::table('automation_executions')
                        ->where('automation_id', $automation->id)
                        ->where('email_id', $msg['message_id'])
                        ->update([
                            'status' => $status,
                            'action_result' => json_encode($execResult),
                            'updated_at' => now(),
                        ]);
                } else {
                    // Marca como skipped
                    DB::table('automation_executions')
                        ->where('automation_id', $automation->id)
                        ->where('email_id', $msg['message_id'])
                        ->update([
                            'status' => 'skipped',
                            'updated_at' => now(),
                        ]);
                }
            }
        }
        
        $lastCheck = now();
        sleep($interval);
    }
    
    Cache::forget(self::LOCK_KEY);
    return self::SUCCESS;
}
```

**Fluxo resumido:**
1. Lock para evitar múltiplas instâncias
2. Loop infinito a cada `$interval` segundos
3. Busca automações ativas com Gmail
4. Para cada automação, busca e-mails novos via `GmailMessageFetcher`
5. **Query Gmail**: `after:{timestamp} -in:sent -in:drafts` (evita loop de encaminhamento)
6. Verifica se e-mail já foi processado (por `message_id`)
7. Decide se deve executar (IA ou match direto)
8. **Marca como `processing` ANTES de executar** (evita duplicação em caso de race condition)
9. Executa ação via `EmailAutomationExecutor`
10. Atualiza status final (`success`, `failed` ou `skipped`)

#### 2.3 Por que `-in:sent -in:drafts`?

**Problema anterior:** Gmail salvava e-mails encaminhados na pasta SENT, causando loop infinito (sistema reprocessava próprios encaminhamentos).

**Solução:** Excluir pastas SENT e DRAFTS da query.

```php
$query = "after:{$timestamp} -in:sent -in:drafts";
```

#### 2.4 Por que `message_id` e não `id`?

**Problema anterior:** `$msg['id']` (ID do Gmail) varia a cada busca, causando duplicação.

**Solução:** Usar `Message-ID` (header permanente único do e-mail).

```php
// GmailMessageFetcher extrai o header correto
'message_id' => $headers->get('Message-ID')['value'] 
    ?? $headers->get('Message-Id')['value'] 
    ?? null
```

#### 2.5 Prevenção de Duplicação (3 camadas)

1. **Verificação antes de processar:**
```php
$alreadyProcessed = DB::table('automation_executions')
    ->where('automation_id', $automation->id)
    ->where('email_id', $msg['message_id'])
    ->exists();

if ($alreadyProcessed) continue;
```

2. **Unique constraint no banco:**
```php
// Migration: automation_executions
$table->unique(['automation_id', 'email_id']);
```

3. **Status `processing` antes de executar:**
```php
// Marca PRIMEIRO, executa DEPOIS
DB::table('automation_executions')->insert([
    'status' => 'processing',
    // ...
]);

// Se falhar no INSERT (duplicado), pula
try { ... } catch (\Exception $e) { continue; }
```

---

### **ETAPA 3: Execução da Ação**

#### 3.1 EmailAutomationExecutor

**Arquivo:** `app/Domain/Automations/Services/Executors/EmailAutomationExecutor.php`

```php
public function execute(Automation $automation, array $event): array
{
    // 1. Valida status
    if ($automation->status !== Automation::STATUS_ACTIVE) {
        return [
            'status' => 'skipped',
            'message' => 'Automação não está ativa',
        ];
    }
    
    // 2. Busca ação
    $action = $automation->actions()->orderBy('position')->first();
    
    if (!$action) {
        return [
            'status' => 'failed',
            'message' => 'Nenhuma ação configurada',
        ];
    }
    
    // 3. Executa ação
    return match($action->type) {
        'encaminhar' => $this->forward($action, $event),
        'responder' => $this->reply($action, $event),
        'organizar' => $this->organize($action, $event),
        'tarefa' => $this->createTask($action, $event),
        default => [
            'status' => 'failed',
            'message' => "Ação '{$action->type}' não implementada",
        ],
    };
}

private function forward(AutomationAction $action, array $event): array
{
    $config = $action->config;
    $forwardTo = $config['forward_to'] ?? [];
    
    if (empty($forwardTo)) {
        return [
            'status' => 'failed',
            'message' => 'Nenhum destinatário configurado',
        ];
    }
    
    // Usa EmailSenderManager para enviar
    $result = $this->emailSender->send(
        to: $forwardTo,
        subject: "Fwd: " . ($event['subject'] ?? ''),
        body: $event['body'] ?? '',
        from: $config['from'] ?? null,
    );
    
    return [
        'status' => $result['success'] ? 'executed' : 'failed',
        'message' => $result['message'],
        'context' => [
            'forward_to' => $forwardTo,
            'original_from' => $event['from'],
        ],
    ];
}
```

**Tipos de ação:**
- `encaminhar`: Usa `EmailSenderManager` para reenviar
- `responder`: Responde ao remetente original
- `organizar`: Move para pasta/label no Gmail (TODO)
- `tarefa`: Cria task em sistema de tarefas (TODO)

#### 3.2 ActionDecisionEngine (IA)

**Arquivo:** `app/Domain/AI/Services/Automations/ActionDecisionEngine.php`

Usado quando `uses_ai=true`:

```php
public function decide(
    string $planRule,
    array $event,
    array $availableActions
): array {
    // 1. Limpa evento de HTML/scripts
    $cleanedEvent = $this->cleanEvent($event);
    
    // 2. Monta prompt
    $prompt = new EmailActionExecutionPrompt(
        planRule: $planRule,
        emailData: $cleanedEvent,
        availableActions: $availableActions,
    );
    
    // 3. Envia para LLM (PrismClient)
    $response = $this->llm->generate($prompt->toArray());
    
    // 4. Parseia resposta JSON
    $decision = json_decode($response['content'], true);
    
    // 5. Normaliza e valida
    return [
        'should_execute' => $decision['should_execute'] ?? false,
        'confidence' => $this->normalizeConfidence($decision['confidence']),
        'matched_conditions' => $decision['matched_conditions'] ?? [],
        'action_to_execute' => $decision['action_to_execute'] ?? null,
        'action_parameters' => $decision['action_parameters'] ?? [],
        'reasoning' => $decision['reasoning'] ?? '',
    ];
}
```

**Prompt enviado para LLM:**
```json
{
  "plan_rule": "Se o e-mail for de financeiro@empresa.com",
  "email_data": {
    "from": "financeiro@empresa.com",
    "subject": "Nota Fiscal #1234",
    "body": "Segue em anexo a nota fiscal..."
  },
  "available_actions": [
    {
      "type": "encaminhar",
      "config": {
        "forward_to": ["contabilidade@empresa.com"]
      }
    }
  ]
}
```

**Resposta esperada do LLM:**
```json
{
  "should_execute": true,
  "confidence": 0.95,
  "matched_conditions": ["from contém financeiro@empresa.com"],
  "action_to_execute": "encaminhar",
  "action_parameters": {
    "forward_to": ["contabilidade@empresa.com"]
  },
  "reasoning": "E-mail é de financeiro@empresa.com, condição atendida com alta confiança."
}
```

---

## 🗄️ Banco de Dados e Migrations

### Tabelas Principais

#### 1. `automation_events`
Catálogo de eventos disponíveis.

```sql
CREATE TABLE automation_events (
    id BIGINT PRIMARY KEY,
    key VARCHAR(255) UNIQUE,        -- 'email_received'
    title VARCHAR(255),              -- 'E-mail recebido'
    description TEXT,
    icon VARCHAR(255),
    category VARCHAR(255),
    position INT,
    active BOOLEAN DEFAULT true,
    metadata JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Seed:**
```php
AutomationEvent::create([
    'key' => 'email_received',
    'title' => 'E-mail recebido',
    'description' => 'Dispara quando um novo e-mail chega na caixa de entrada',
    'icon' => 'lucide:mail',
    'category' => 'email',
    'position' => 1,
    'active' => true,
]);
```

#### 2. `automation_trigger_types`
Tipos de trigger por evento.

```sql
CREATE TABLE automation_trigger_types (
    id BIGINT PRIMARY KEY,
    automation_event_id BIGINT,     -- FK para automation_events
    key VARCHAR(255),                -- 'sender_exact', 'content_semantic'
    title VARCHAR(255),
    description TEXT,
    icon VARCHAR(255),
    placeholder VARCHAR(255),
    uses_ai BOOLEAN DEFAULT false,
    validation_rules JSON,
    position INT,
    active BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (automation_event_id) REFERENCES automation_events(id)
);
```

**Seeds:**
```php
AutomationTriggerType::create([
    'automation_event_id' => $emailReceivedEvent->id,
    'key' => 'sender_exact',
    'title' => 'Remetente exato',
    'description' => 'Quando o e-mail for enviado por um endereço específico',
    'icon' => 'lucide:at-sign',
    'placeholder' => 'ex: contato@empresa.com',
    'uses_ai' => false,
    'position' => 1,
]);

AutomationTriggerType::create([
    'automation_event_id' => $emailReceivedEvent->id,
    'key' => 'content_semantic',
    'title' => 'Contexto semântico (IA)',
    'description' => 'IA analisa o conteúdo do e-mail e decide se deve executar',
    'icon' => 'lucide:brain',
    'placeholder' => 'ex: E-mails que contenham nota fiscal ou boleto',
    'uses_ai' => true,
    'position' => 4,
]);
```

#### 3. `automations`
Automações criadas pelos usuários.

```sql
CREATE TABLE automations (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,
    integration_id BIGINT,
    automation_event_id BIGINT,     -- FK para automation_events
    trigger_type_id BIGINT,         -- FK para automation_trigger_types
    rule_text TEXT,                 -- Regra original do usuário
    plan_rule_text TEXT,            -- Regra otimizada pela IA
    status ENUM('draft', 'active', 'paused') DEFAULT 'draft',
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (integration_id) REFERENCES integrations(id),
    FOREIGN KEY (automation_event_id) REFERENCES automation_events(id),
    FOREIGN KEY (trigger_type_id) REFERENCES automation_trigger_types(id)
);
```

**Campos importantes:**
- `rule_text`: Texto original digitado pelo usuário
- `plan_rule_text`: Versão otimizada gerada por `EmailRulePlanner` (LLM)
- `status`: `draft` (rascunho), `active` (rodando), `paused` (pausada)

#### 4. `automation_actions`
Ações de cada automação.

```sql
CREATE TABLE automation_actions (
    id BIGINT PRIMARY KEY,
    automation_id BIGINT,
    type VARCHAR(255),              -- 'encaminhar', 'responder', 'organizar'
    config JSON,                    -- { "forward_to": ["email@dest.com"] }
    position INT DEFAULT 1,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (automation_id) REFERENCES automations(id) ON DELETE CASCADE
);
```

**Exemplo de `config` para encaminhar:**
```json
{
  "forward_to": ["financeiro@empresa.com", "contabilidade@empresa.com"]
}
```

#### 5. `automation_executions`
Histórico de execuções.

```sql
CREATE TABLE automation_executions (
    id BIGINT PRIMARY KEY,
    automation_id BIGINT,
    email_id VARCHAR(500),          -- Message-ID do e-mail
    email_subject TEXT,
    email_from VARCHAR(255),
    status ENUM('processing', 'success', 'failed', 'skipped'),
    reasoning TEXT,                 -- Decisão da IA
    confidence DECIMAL(5,4),        -- 0.0000 a 1.0000
    action_result JSON,             -- Resultado da execução
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (automation_id) REFERENCES automations(id) ON DELETE CASCADE,
    UNIQUE KEY unique_automation_email (automation_id, email_id)
);
```

**Status:**
- `processing`: Marcado antes de executar (evita duplicação)
- `success`: Ação executada com sucesso
- `failed`: Erro na execução
- `skipped`: IA/match decidiu não executar

**Unique constraint:**
```sql
UNIQUE KEY unique_automation_email (automation_id, email_id)
```
Garante que mesma automação não processa mesmo e-mail 2x.

### Migrations (ordem cronológica)

```
2026_01_14_000300_create_automations_table.php
2026_01_14_000400_create_automation_actions_table.php
2026_01_14_000500_add_plan_rule_text_to_automations_table.php
2026_01_14_174005_add_trigger_type_id_to_automations_table.php
2026_01_14_174113_create_automation_events_table.php
2026_01_14_174114_create_automation_trigger_types_table.php
2026_01_14_174154_add_automation_event_id_to_automations_table.php
2026_01_14_174817_remove_event_from_automations_table.php
2026_01_14_205225_create_automation_executions_table.php
2026_01_14_205547_update_automation_executions_subject_length.php
2026_01_14_214506_add_processing_status_to_automation_executions.php
```

---

## ⚙️ Watcher: Como Funciona

### Iniciar Watcher

```bash
php artisan automations:watch
```

**Flags:**
- `--interval=30`: Verifica a cada 30 segundos (padrão: 30, mínimo: 10)
- `--force`: Ignora lock, permite múltiplas instâncias (não recomendado)

### Lock Mechanism

```php
private const LOCK_KEY = 'automations:watch:lock';
private const LOCK_TTL = 300; // 5 minutos

// Tenta adquirir lock
if (!Cache::add(self::LOCK_KEY, true, self::LOCK_TTL)) {
    $this->error('❌ Já existe uma instância do watcher rodando!');
    return self::FAILURE;
}

// Renova lock a cada iteração
Cache::put(self::LOCK_KEY, true, self::LOCK_TTL);

// Libera lock ao sair
Cache::forget(self::LOCK_KEY);
```

**Por que?** Evita múltiplas instâncias processando os mesmos e-mails.

### Graceful Shutdown

```php
pcntl_async_signals(true);
pcntl_signal(SIGINT, function () {
    $this->shouldStop = true;
    $this->warn("\n🛑 Parando observador...");
});

while (!$this->shouldStop) {
    // Loop principal
    sleep($interval);
}
```

**Ctrl+C** para parar de forma segura, liberando lock.

### Gmail Query

```php
$timestamp = $lastCheck->timestamp;
$query = "after:{$timestamp} -in:sent -in:drafts";

$result = $fetcher->fetch($automation->integration, $query, 20);
```

**Operadores Gmail:**
- `after:{timestamp}`: E-mails após data Unix
- `-in:sent`: Exclui pasta de enviados
- `-in:drafts`: Exclui rascunhos
- Limit: 20 e-mails por vez

### Match Direto (sem IA)

```php
private function matchDirect(Automation $automation, array $event): bool
{
    $triggerKey = $automation->triggerType->key;
    $rule = $automation->rule_text;
    
    // Extrai e-mail de "Nome <email@dominio.com>"
    preg_match('/<(.+?)>/', $event['from'], $emailMatches);
    $email = $emailMatches[1] ?? $event['from'];
    
    return match($triggerKey) {
        'sender_exact' => strcasecmp($email, trim($rule)) === 0,
        'sender_domain' => str_contains(strtolower($email), '@' . str_replace('@', '', strtolower(trim($rule)))),
        'subject_contains' => stripos($event['subject'], $rule) !== false,
        default => false,
    };
}
```

**Tipos suportados:**
- `sender_exact`: Comparação case-insensitive exata
- `sender_domain`: Verifica se domínio está presente
- `subject_contains`: Busca substring no assunto

---

## 🛠️ Como Criar Novas Automações

### Cenário: Adicionar ação "Marcar como lido"

#### 1. Adicionar suporte no Executor

**Arquivo:** `app/Domain/Automations/Services/Executors/EmailAutomationExecutor.php`

```php
public function execute(Automation $automation, array $event): array
{
    $action = $automation->actions()->orderBy('position')->first();
    
    return match($action->type) {
        'encaminhar' => $this->forward($action, $event),
        'responder' => $this->reply($action, $event),
        'organizar' => $this->organize($action, $event),
        'tarefa' => $this->createTask($action, $event),
        'marcar_lido' => $this->markAsRead($action, $event), // NOVO
        default => [
            'status' => 'failed',
            'message' => "Ação '{$action->type}' não implementada",
        ],
    };
}

private function markAsRead(AutomationAction $action, array $event): array
{
    // TODO: Implementar integração com Gmail API
    // Modificar label UNREAD do e-mail
    
    return [
        'status' => 'executed',
        'message' => 'E-mail marcado como lido',
    ];
}
```

#### 2. Adicionar no Frontend

**Arquivo:** `resources/js/Pages/Automations/EmailReceived.vue`

```javascript
const actions = [
  {
    key: 'organizar',
    title: 'Organizar e classificar',
    description: 'Mover para pasta e adicionar marcadores.',
    icon: 'lucide:folder'
  },
  {
    key: 'encaminhar',
    title: 'Encaminhar e-mail',
    description: 'Enviar para outra pessoa automaticamente.',
    icon: 'lucide:send'
  },
  // NOVO
  {
    key: 'marcar_lido',
    title: 'Marcar como lido',
    description: 'Remove a flag de não lido do e-mail.',
    icon: 'lucide:eye'
  },
]
```

#### 3. Testar

1. Criar automação com ação "Marcar como lido"
2. Executar watcher: `php artisan automations:watch`
3. Enviar e-mail de teste
4. Verificar em `automation_executions` se foi executado

---

### Cenário: Adicionar trigger "Tem anexo"

#### 1. Criar Trigger Type

**Seeder:**
```php
AutomationTriggerType::create([
    'automation_event_id' => $emailReceivedEvent->id,
    'key' => 'has_attachment',
    'title' => 'Possui anexo',
    'description' => 'Quando o e-mail tiver arquivos anexados',
    'icon' => 'lucide:paperclip',
    'placeholder' => 'Qualquer tipo de anexo',
    'uses_ai' => false,
    'position' => 5,
]);
```

#### 2. Implementar Match

**Arquivo:** `app/Console/Commands/WatchEmailAutomations.php`

```php
private function matchDirect(Automation $automation, array $event): bool
{
    $triggerKey = $automation->triggerType->key;
    $rule = $automation->rule_text;
    
    return match($triggerKey) {
        'sender_exact' => strcasecmp($email, trim($rule)) === 0,
        'sender_domain' => str_contains(strtolower($email), '@' . str_replace('@', '', strtolower(trim($rule)))),
        'subject_contains' => stripos($event['subject'], $rule) !== false,
        'has_attachment' => !empty($event['attachments']), // NOVO
        default => false,
    };
}
```

#### 3. Extrair Anexos no Fetcher

**Arquivo:** `app/Domain/Integrations/Services/GmailMessageFetcher.php`

```php
private function parseMessage(array $response, string $id): array
{
    $headers = collect($response['payload']['headers'] ?? [])
        ->keyBy('name');
    
    $body = $this->extractBody($response['payload'] ?? []);
    
    // NOVO: Extrair anexos
    $attachments = $this->extractAttachments($response['payload'] ?? []);
    
    return [
        'id' => $id,
        'message_id' => $headers->get('Message-ID')['value'] ?? null,
        'from' => $headers->get('From')['value'] ?? null,
        'subject' => $headers->get('Subject')['value'] ?? null,
        'date' => $headers->get('Date')['value'] ?? null,
        'snippet' => $response['snippet'],
        'body' => $body,
        'attachments' => $attachments, // NOVO
    ];
}

private function extractAttachments(array $payload): array
{
    $parts = $payload['parts'] ?? [];
    $attachments = [];
    
    foreach ($parts as $part) {
        if (!empty($part['filename'])) {
            $attachments[] = [
                'filename' => $part['filename'],
                'mimeType' => $part['mimeType'] ?? null,
                'size' => $part['body']['size'] ?? 0,
            ];
        }
    }
    
    return $attachments;
}
```

---

## 🔍 Troubleshooting

### Problema: E-mails duplicados

**Sintomas:** Mesmo e-mail processado múltiplas vezes.

**Causa:** `message_id` está vazio ou unique constraint não está ativa.

**Solução:**
1. Verificar se `GmailMessageFetcher` está extraindo `Message-ID`:
```php
'message_id' => $headers->get('Message-ID')['value'] 
    ?? $headers->get('Message-Id')['value'] 
    ?? null
```

2. Verificar unique constraint:
```bash
php artisan migrate:status
```

3. Adicionar log no watcher:
```php
if (empty($msg['message_id'])) {
    $this->warn("⚠️  E-mail sem Message-ID, pulando...");
    continue;
}
```

---

### Problema: Loop infinito de encaminhamentos

**Sintomas:** Sistema encaminha e-mail, Gmail salva na SENT, sistema reprocessa próprio encaminhamento.

**Causa:** Query não exclui pasta SENT.

**Solução:**
```php
$query = "after:{$timestamp} -in:sent -in:drafts";
```

---

### Problema: Lock não libera

**Sintomas:** Watcher não inicia, diz que já existe instância rodando.

**Causa:** Lock ficou travado (ex: watcher matado com `kill -9`).

**Solução:**
1. Limpar cache:
```bash
php artisan cache:clear
```

2. Ou forçar execução:
```bash
php artisan automations:watch --force
```

---

### Problema: IA não decide corretamente

**Sintomas:** E-mails que deveriam executar são marcados como `skipped`.

**Causa:** `plan_rule_text` mal formatado ou prompt da IA inadequado.

**Solução:**
1. Verificar `plan_rule_text` gerado:
```sql
SELECT id, rule_text, plan_rule_text FROM automations WHERE id = ?;
```

2. Testar decisão manualmente:
```php
$decision = app(ActionDecisionEngine::class)->decide(
    planRule: 'Se o e-mail for de financeiro@empresa.com',
    event: [
        'from' => 'financeiro@empresa.com',
        'subject' => 'Nota Fiscal',
        'body' => 'Segue anexo',
    ],
    availableActions: [
        ['type' => 'encaminhar', 'config' => ['forward_to' => ['teste@empresa.com']]]
    ]
);

dd($decision);
```

3. Ajustar prompt em `EmailActionExecutionPrompt.php`

---

### Problema: Gmail OAuth expirado

**Sintomas:** `401 Unauthorized` ao buscar e-mails.

**Causa:** Token OAuth2 expirou e refresh falhou.

**Solução:**
1. `GmailMessageFetcher` tenta refresh automaticamente:
```php
if ($response->status() === 401) {
    $this->refreshAccessToken($integration);
    // Retry request
}
```

2. Se falhar, usuário precisa reconectar:
```
Vá em /integrations → Desconectar → Conectar novamente
```

---

## 📚 Referências Rápidas

### Rotas Principais

| Rota | Método | Ação |
|------|--------|------|
| `/automations` | GET | Lista automações |
| `/automations/email-received` | GET | Formulário criação |
| `/automations/email-received/{id}` | GET | Formulário edição |
| `/automations/email-received/simulate` | POST | Simula automação |
| `/automations/email-received/{id?}` | POST | Salva automação |
| `/automations/{id}/status` | PATCH | Atualiza status |
| `/automations/{id}` | DELETE | Remove automação |

### Comandos Artisan

```bash
# Iniciar watcher
php artisan automations:watch

# Watcher com intervalo customizado
php artisan automations:watch --interval=60

# Forçar execução (ignorar lock)
php artisan automations:watch --force

# Limpar cache (liberar lock)
php artisan cache:clear
```

### Models Principais

```php
use App\Domain\Automations\Models\Automation;
use App\Domain\Automations\Models\AutomationEvent;
use App\Domain\Automations\Models\AutomationTriggerType;
use App\Domain\Automations\Models\AutomationAction;
```

### Services Principais

```php
use App\Domain\Automations\Services\Executors\EmailAutomationExecutor;
use App\Domain\AI\Services\Automations\ActionDecisionEngine;
use App\Domain\Integrations\Services\GmailMessageFetcher;
```

---

## ✅ Checklist para Criar Nova Automação

- [ ] Definir evento (ex: `task_created`)
- [ ] Criar seed em `automation_events`
- [ ] Criar triggers em `automation_trigger_types`
- [ ] Implementar match em `WatchEmailAutomations::matchDirect()`
- [ ] Adicionar ação em `EmailAutomationExecutor::execute()`
- [ ] Atualizar frontend em `EmailReceived.vue`
- [ ] Testar com `php artisan automations:watch`
- [ ] Verificar histórico em `automation_executions`

---

**Criado em:** 2026-01-15  
**Autor:** NanoEase Team  
**Versão:** 1.0

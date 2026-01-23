🧠 Guia Arquitetural Definitivo – Plataforma de Automação Operacional Inteligente

---

## ⚠️ ATENÇÃO CRÍTICA - MULTI-TENANCY (SaaS)

**REGRA OBRIGATÓRIA**: Este é um sistema SaaS multi-tenant. **TODA** tabela que armazena dados de negócio DEVE ter `team_id`.

### Por quê?
- Cada conta (team) deve ser completamente isolada
- Previne vazamento de dados entre clientes
- Permite queries eficientes por tenant
- Essencial para compliance e segurança

### Checklist de criação de tabela:
```php
Schema::create('nome_tabela', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete(); // ✅ OBRIGATÓRIO
    // ... restante dos campos
    
    // Índices sempre incluem team_id
    $table->index(['team_id', 'user_id', 'status']);
});
```

### Tabelas que JÁ PRECISAM de team_id (migrations criadas):
- ✅ `integrations` - migration: 2026_01_23_212511
- ✅ `automations` - migration: 2026_01_23_212515
- ✅ `mass_email_sends` - migration: 2026_01_23_212519
- ✅ `automation_executions` - migration: 2026_01_23_212606
- ✅ `classified_emails` - migration: 2026_01_23_212606

### Models devem ter:
```php
protected $fillable = ['team_id', 'user_id', ...];

// Scope global (considerar implementar)
protected static function booted()
{
    static::addGlobalScope('team', function (Builder $query) {
        if (auth()->check() && auth()->user()->currentTeam) {
            $query->where('team_id', auth()->user()->currentTeam->id);
        }
    });
}
```

### Controllers/Actions devem validar:
```php
// SEMPRE passar team_id ao criar
$model->create([
    'team_id' => $user->currentTeam->id,
    'user_id' => $user->id,
    // ...
]);

// SEMPRE filtrar por team ao buscar
$items = Model::where('team_id', $user->currentTeam->id)->get();
```

**Se você está criando uma nova feature e esqueceu do team_id, PARE e corrija imediatamente.**

---

Este arquivo é a constituição técnica e conceitual do projeto.

Ele existe para:
	•	Guiar o Copilot / IA de código
	•	Impor disciplina arquitetural
	•	Evitar explosão de escopo
	•	Garantir que o produto permaneça simples para o usuário e sustentável para o time

Nada neste arquivo é sugestão. Tudo é regra.

⸻

1. Visão do Produto (Imutável)

Estamos construindo uma plataforma de automação de rotinas operacionais para pequenas e médias empresas.

O produto não é:
	•	um n8n simplificado
	•	um Zapier concorrente
	•	uma ferramenta técnica

O produto é:

Um motor simples que transforma eventos do dia a dia em ações úteis, usando linguagem natural.

Modelo mental único do sistema:

Evento → Interpretação → Ação

Se algo não se encaixa nesse modelo, não entra no produto.

⸻

2. Princípios de UX (Obrigatórios)
	•	Usuário não vê lógica
	•	Usuário não monta fluxos
	•	Usuário não aprende termos técnicos

É proibido expor:
	•	AND / OR / IF
	•	canvas visual
	•	nós, setas, diagramas
	•	termos como trigger, webhook, regex

Se o usuário precisar pensar como programador, o design falhou.

⸻

3. Stack Tecnológica
	•	Backend: Laravel
	•	Frontend: Vue.js
	•	Comunicação: Inertia.js
	•	Rotas: somente webNomeModulo.php ex: webAutomations.php
	•	❌ Não criar API REST

⸻

4. Regra Máxima de Organização

❌ Nunca usar app/Http
	•	Nenhum Controller
	•	Nenhum Request
	•	Nenhuma lógica

Tudo vive em Domínios.

⸻

5. Organização por Domínio (Obrigatória)

Estrutura padrão:

app/
└── Domain/
    └── ExampleDomain/
        ├── Controllers/
        ├── Models/
        ├── Actions/
        ├── Services/
        ├── Requests/
        └── Routes/
            └── webNomeModulo.php ex: webAutomations.php

Responsabilidades por camada

Controllers
	•	Apenas orquestram chamadas
	•	Nunca contêm regra de negócio
	•	Nunca chamam IA diretamente

Models
	•	Apenas estrutura de dados
	•	Nenhuma regra complexa

Actions
	•	Cada Action = um caso de uso
	•	Um arquivo por ação (Store, Update, Run, Execute)
	•	Não conhecem HTTP nem UI

Services
	•	Regras reutilizáveis do domínio
	•	Não recebem Request
	•	Não retornam Response

Requests
	•	Apenas validação
	•	Zero regra de negócio

⸻

6. Rotas
	•	Cada domínio possui seu próprio Routes/web.php
	•	routes/web.php principal apenas importa os domínios
	•	Nenhuma rota definida fora dos domínios

⸻

7. Domínios do Sistema

Domínios iniciais (criar estrutura mesmo que vazia):
	•	Users
	•	Automations
	•	Events
	•	Actions
	•	Executions
	•	Integrations
	•	AI

⸻

8. Core de Automação (Coração do Produto)

Estrutura conceitual

Automation
 ├── Event
 ├── Rule (texto em linguagem natural)
 ├── Action
 └── Status

Fluxo interno obrigatório
	1.	Evento ocorre
	2.	Evento é normalizado
	3.	IA interpreta a regra
	4.	Sistema decide executar ou ignorar
	5.	Ação é executada
	6.	Execução é registrada

⚠️ IA nunca executa nada sozinha.

⸻

9. Evento Inicial (MVP)

📧 EmailReceived

Motivos:
	•	Universal
	•	Fácil de integrar
	•	Origem de muitas dores operacionais

Outros eventos só entram após o MVP estar sólido.

⸻

10. Ações do MVP
	•	Organizar/classificar e-mail
	•	Encaminhar e-mail
	•	Responder automaticamente
	•	Criar tarefa interna

Cada ação é um arquivo independente.

⸻

11. Organização de IA (Prism) – Regra Crítica

Toda IA fica isolada em um domínio próprio.

app/Domain/AI/
├── Prompts/
│   ├── BasePrompt.php
│   ├── IntentDetectionPrompt.php
│   ├── EmailClassificationPrompt.php
│   └── RuleInterpretationPrompt.php
├── Services/
│   ├── PrismClient.php
│   ├── IntentInterpreter.php
│   └── ClassificationService.php
└── Contracts/

Regras obrigatórias de IA
	•	❌ Nunca chamar LLM fora do domínio AI
	•	❌ Nunca escrever prompt inline
	•	❌ Nunca misturar prompt com regra de negócio
	•	✅ IA retorna interpretação, nunca decisão final

⸻

12. Logs e Execuções

Toda execução deve ser registrada:
	•	Executado
	•	Ignorado
	•	Erro

Logs são simples, legíveis e visíveis para o usuário.

⸻

13. Frontend (Vue + Inertia)

Regras de componentes
	•	Máximo 200 linhas por componente
	•	Se passar disso, deve ser quebrado

Telas obrigatórias
	1.	Login
	2.	Dashboard
	3.	Criar automação (wizard 3 passos)
	4.	Simulação
	5.	Detalhe da automação
	6.	Integrações

⸻

14. Regras de Evolução do Produto (Anti-Monstro)
	•	Nunca adicionar novo Evento e nova Ação ao mesmo tempo
	•	Sempre validar reutilização antes de criar algo novo
	•	Nunca expor lógica técnica ao usuário
	•	Crescimento deve ser incremental

⸻

15. Checklist de Execução

Fundação
	•	Criar estrutura Domain
	•	Configurar Inertia + Vue
	•	Definir layout base

MVP Email
	•	Integração Gmail
	•	Captura de EmailReceived
	•	Normalização de payload

IA
	•	Estrutura Domain/AI
	•	Prompts separados
	•	DTOs de interpretação

Automações
	•	Criar Automation
	•	Criar Execution
	•	Criar Actions do MVP

UX
	•	Wizard 3 passos
	•	Simulação antes de ativar
	•	Logs visíveis

⸻

16. Regra Final

Este projeto vence por clareza, não por poder técnico.

Sempre que surgir a dúvida:

“Isso está simples o suficiente para qualquer pessoa usar?”

Se a resposta for não, não implemente.

⸻

## 17. Automação para Envio de E-mail (Envio Operacional em Lote)

Esta automação permite o envio de **e-mails operacionais para múltiplos destinatários**, como convites, comunicados e avisos importantes.

⚠️ Este módulo **NÃO é e-mail marketing**.  
Ele existe para eliminar envios manuais repetitivos feitos “na mão”.

Exemplos de uso válidos:
- Convite para reunião enviado para vários clientes
- Comunicado importante
- Aviso operacional
- Confirmação ou lembrete

---

## 17.1 Princípio do Módulo

Este módulo segue o modelo central do sistema:

> **Evento → Interpretação → Ação**

Aqui o envio é **ativo**, iniciado pelo usuário ou por agendamento.

---

## 17.2 Evento (Gatilho)

No MVP, suportar apenas:

- **Envio manual**
- **Envio agendado (data/hora)**

❌ Não implementar campanhas recorrentes  
❌ Não implementar sequências automáticas

---

## 17.3 Fonte de Destinatários (Obrigatório)

O envio em lote **sempre depende de uma fonte estruturada**.

Fontes suportadas:
- Upload de CSV
- Google Sheets

Ambas devem seguir o **MESMO CONTRATO DE DADOS**.

---

## 17.4 Modelo Obrigatório de Planilha / CSV (Contrato de Dados)

Para que a automação funcione, a planilha ou CSV **DEVE conter**:

### Colunas obrigatórias
- **email** → endereço do destinatário

### Colunas opcionais
- nome
- empresa
- qualquer outra coluna usada como variável no e-mail

### Exemplo de estrutura válida

| email              | nome   | empresa       |
|-------------------|--------|---------------|
| joao@email.com     | João   | Empresa X     |
| maria@email.com    | Maria  | Empresa Y     |

⚠️ Regras:
- A coluna de e-mail deve conter **e-mails válidos**
- Cada linha representa **um destinatário**
- Linhas sem e-mail válido são ignoradas

---

## 17.5 Validação da Fonte (Obrigatória)

Antes de permitir o envio, o sistema DEVE:

- Validar existência da coluna de e-mail
- Validar formato dos e-mails
- Deduplicar endereços
- Mostrar resumo ao usuário:
  - Total de linhas
  - Válidos
  - Ignorados (com motivo)

Se a validação falhar, o envio **NÃO pode ser iniciado**.
Deve permitir salvar os emails para uso futuro, mas bloquear o envio até corrigir os erros.

---

## 17.6 Regra / Interpretação

A regra define **como o envio será feito**, não quem recebe.

Configuração:
- Fonte de dados (CSV ou Sheets)
- Mapeamento de colunas → variáveis (`{{nome}}`, `{{empresa}}`)
- Enviar agora ou agendar

### Uso de IA (Obrigatório, mas controlado)

A IA **NÃO decide destinatários**.

Usos permitidos:
- Melhorar clareza do texto
- Ajustar tom (formal / direto)
- Corrigir gramática
- Sugerir assunto melhor

⚠️ Regras:
- IA nunca altera a lista
- IA nunca dispara envio
- Usuário sempre revisa e confirma

Toda IA deve estar no domínio `Domain/AI`.

---

## 17.7 Ação

A ação executada é:

- **Enviar e-mail para cada destinatário válido**

Características obrigatórias:
- Envio via fila (queue)
- Rate limit por usuário
- Deduplicação
- Registro de status por destinatário

Status possíveis:
- enviado
- falhou
- ignorado (inválido / duplicado / descadastrado)

---

## 17.8 Limites e Proteções (Obrigatórios)

- Limite diário de envios por usuário
- Validação de e-mail
- Link de descadastro automático
- Bloqueio de listas grandes no MVP
- Logs completos

---

## 17.9 UX (Wizard Simples)

1. Criar envio
   - Nome
   - Enviar agora ou agendar
2. Destinatários
   - Escolher CSV, ou Google Sheets
   - Visualizar colunas
   - Mapear coluna de e-mail
3. Mensagem
   - Assunto
   - Corpo (com variáveis)
   - Botão “Melhorar texto com IA”
4. Revisão
   - Prévia com exemplos reais
   - Envio de teste
   - Resumo da validação
5. Envio e relatório
   - Status por destinatário

---

## 17.10 Regra de Evolução

- Não adicionar filtros complexos no MVP
- Não adicionar recorrência
- Não permitir listas sem validação
- Crescer apenas após uso real

---

## 17.11 Regra Final

Sem contrato de dados, não há automação.

Se a planilha não seguir o modelo,
o sistema deve bloquear o envio e explicar o motivo.

## 18. Sistema de Tarefas (Kanban Operacional)

Este módulo implementa um **sistema central de tarefas em formato Kanban**, que pode ser utilizado de forma manual ou alimentado por automações do sistema.

O objetivo é concentrar **tudo que precisa ser feito** em um único lugar, independentemente da origem.

---

## 18.1 Princípio do Módulo

O sistema de tarefas é **independente de e-mail**, mas pode ser integrado a:

- E-mails recebidos
- Automações do sistema
- Criação manual
- Formulários públicos externos

As tarefas representam **atividades executáveis**, não apenas lembretes.

---

## 18.2 Estrutura do Kanban

O Kanban é composto por **listas** (fases) e **cartões** (tarefas).

### Listas (Fases)
- Representam o estágio da tarefa
- O sistema fornece listas padrão
- O usuário pode:
  - renomear listas
  - adicionar novas listas
  - reordenar listas

#### Listas padrão sugeridas
- A Fazer
- Em Andamento
- Concluído

⚠️ As listas são configuráveis por usuário/conta.

---

## 18.3 Cartão de Tarefa

Cada cartão de tarefa deve conter:

- **Nome da atividade** (título)
- **Descrição**
- **Responsável**
- **Data de vencimento**
- **Etiquetas (labels)**
- **Origem da atividade**
- **Histórico de eventos**

### Origem da atividade (obrigatório)
Exemplos:
- Criação manual
- Automação de e-mail
- Automação do sistema
- Formulário externo

A origem deve ser visível no cartão.

---

## 18.4 Interações do Kanban

Funcionalidades obrigatórias:
- Arrastar e soltar cartões entre listas
- Atualização automática do status ao mover
- Indicador visual de vencimento:
  - atrasado
  - vence hoje
  - futuro

---

## 18.5 Criação de Tarefas

As tarefas podem ser criadas de quatro formas:

### 1. Criação Manual
- Usuário cria a tarefa diretamente no Kanban
- Preenche campos manualmente

---

### 2. Criação por Automação
- Ação de uma automação do sistema
- Exemplo:
  - e-mail recebido → criar tarefa
  - cobrança → criar tarefa
  - reunião → criar tarefa

Uso de IA permitido:
- sugerir título
- gerar descrição
- extrair data
- sugerir etiquetas

⚠️ A IA **não decide criar tarefas sozinha**, apenas executa quando a automação existir.

---

### 3. Criação via Formulário Público (Entrada Externa)

O sistema deve permitir a criação de **formulários públicos** para geração de tarefas.

Fluxo:
- Usuário cria um formulário no sistema
- Define os campos do formulário
- Sistema gera um link público
- Qualquer pessoa pode preencher
- Cada envio cria um cartão no Kanban

Exemplos de uso:
- pedidos de suporte
- solicitações internas
- briefing de clientes
- coleta de demandas

⚠️ O formulário NÃO exige login.

---

### 4. Criação via Integrações Futuras
Este módulo deve ser preparado para:
- webhooks
- APIs internas
- outras fontes de evento

---

## 18.6 Uso de IA no Sistema de Tarefas

A IA pode ser usada para:
- gerar descrição clara da tarefa
- resumir conteúdos longos (ex: e-mails)
- sugerir etiquetas
- identificar prazos no texto

Regras:
- IA nunca cria tarefas sem evento explícito
- IA nunca altera responsável sem confirmação
- Toda sugestão é revisável pelo usuário

Toda lógica de IA deve residir em `Domain/AI`.

---

## 18.7 Histórico da Tarefa

Cada tarefa deve manter um histórico automático:
- criação
- mudança de lista
- alteração de responsável
- alteração de vencimento
- origem da criação

O histórico é somente leitura.

---

## 18.8 UX do Kanban

Requisitos de UX:
- Visual limpo e simples
- Drag & drop fluido
- Cartões com informações essenciais visíveis
- Clique abre detalhes completos
- Origem da tarefa sempre visível

O Kanban deve ser utilizável sem tutorial.

---

## 18.9 Regras de Escopo (Importante)

❌ Não implementar:
- subtarefas no MVP
- comentários
- times complexos
- permissões avançadas
- workflows condicionais

O foco é **execução**, não gerenciamento corporativo.

---

## 18.10 Regra Final

Toda atividade do sistema deve poder virar uma tarefa.

Se algo exige ação humana, deve poder ser representado no Kanban.

📌 Este arquivo deve ser lido antes de escrever código e respeitado durante toda a vida do projeto.
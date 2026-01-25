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

## 19. Organizador de Contratos, Documentos e Vencimentos (com Assistente Jurídico IA)

Este módulo implementa um **sistema centralizado de organização de contratos e documentos jurídicos**, com controle de vencimentos, alertas automáticos e suporte de uma IA especializada em leitura e interpretação contratual.

O objetivo é evitar:
- contratos esquecidos
- renovações automáticas indesejadas
- multas por vencimento
- falta de visibilidade jurídica

---

## 19.1 Princípio do Módulo

Este módulo atua como um **repositório inteligente de contratos**, integrado ao sistema de tarefas e automações.

Fluxo base:
> Documento → Interpretação (IA) → Registro → Alerta → Ação

⚠️ O sistema **NÃO substitui um advogado**.  
Ele organiza informações, gera alertas e auxilia na leitura.

---

## 19.2 Tipos de Documentos Suportados

No MVP, suportar:
- Contratos (PDF, DOCX)
- Aditivos contratuais
- Termos de uso / prestação de serviço

Fontes de entrada:
- Upload manual
- E-mail (automação)
- Formulário externo (opcional futuro)

---

## 19.3 Registro de Contrato

Ao registrar um contrato, o sistema deve armazenar:

- Nome do contrato
- Tipo de contrato (ex: prestação de serviço, aluguel, SaaS)
- Partes envolvidas
- Data de início
- Data de término / vencimento
- Renovação automática (sim/não/desconhecido)
- Valor (se identificado)
- Status (ativo, vencido, encerrado)
- Documento original

---

## 19.4 Uso de IA na Leitura do Contrato

A IA deve auxiliar na **extração e interpretação**, nunca tomar decisões jurídicas.

Funções permitidas:
- Identificar datas importantes
- Identificar cláusulas de renovação
- Identificar multas e prazos
- Resumir o contrato em linguagem simples
- Sinalizar cláusulas potencialmente sensíveis

⚠️ Regras:
- IA deve sempre citar o trecho do contrato
- IA não deve afirmar ilegalidade
- IA não deve dar parecer jurídico definitivo

Toda IA deve residir no domínio `Domain/AI`.

---

## 19.5 Alertas e Vencimentos

O sistema deve permitir configurar alertas automáticos:

Alertas padrão:
- X dias antes do vencimento
- No dia do vencimento
- Após vencimento (se ainda ativo)

Os alertas devem:
- Criar tarefas automaticamente
- Enviar notificações por e-mail
- Aparecer no Kanban

Exemplo:
> “Revisar contrato de prestação de serviços – vence em 15 dias”

---

## 19.6 Integração com Sistema de Tarefas

Cada contrato pode gerar tarefas automaticamente:
- Revisar contrato
- Renovar contrato
- Encerrar contrato
- Negociar reajuste

A tarefa deve conter:
- Link para o contrato
- Data de vencimento
- Origem: contrato

---

## 19.7 Assistente Jurídico IA

O sistema deve oferecer um **Assistente Jurídico IA** treinado para leitura de contratos.

Funcionalidades:
- Perguntas em linguagem natural:
  - “Esse contrato tem renovação automática?”
  - “Existe multa por cancelamento?”
  - “Quais são os principais riscos?”
- Resumo executivo do contrato
- Destaque de cláusulas importantes

⚠️ Aviso obrigatório:
> “Esta análise não substitui um advogado.”

---

## 19.8 Histórico do Contrato

Cada contrato deve manter histórico automático:
- upload
- alterações de status
- alertas disparados
- tarefas criadas
- interações com a IA

O histórico é somente leitura.

---

## 19.9 UX do Módulo

Requisitos de UX:
- Lista de contratos com status visual
- Indicadores de vencimento
- Filtros por tipo, status e data
- Acesso rápido ao documento
- Área de perguntas para IA

A experiência deve ser clara para usuários não jurídicos.

---

## 19.10 Regras de Escopo (Importante)

❌ Não implementar no MVP:
- assinatura digital
- workflows jurídicos complexos
- múltiplas versões de contrato
- parecer jurídico automático

O foco é **organização, alerta e clareza**.

---

## 19.11 Regra Final

Contrato esquecido é risco oculto.

Este módulo existe para garantir que **nenhum contrato vença sem ação**.

## 19.12 Prompt para o agente de IA para ler os contratos

"Você é um **Assistente Jurídico de Apoio**, especializado em leitura e interpretação de contratos.

Seu papel NÃO é substituir um advogado.
Seu papel é **ajudar o usuário a entender o contrato**, destacando informações importantes, riscos práticos e pontos de atenção.

---

## CONTEXTO FORNECIDO
Você receberá:
- Texto completo ou parcial de um contrato
- Pergunta opcional do usuário sobre o contrato

---

## SUAS RESPONSABILIDADES

1. Ler o contrato com atenção
2. Identificar informações relevantes de forma objetiva
3. Responder sempre com base no TEXTO do contrato
4. Citar trechos relevantes como evidência
5. Usar linguagem simples e acessível (não jurídica)

---

## O QUE VOCÊ PODE FAZER

✅ Identificar:
- Datas importantes (início, término, vigência)
- Cláusulas de renovação automática
- Multas por cancelamento
- Prazos de aviso prévio
- Obrigações principais das partes
- Valores, reajustes e periodicidade
- Riscos práticos para o contratante

✅ Resumir:
- O contrato em poucas linhas
- As principais obrigações
- Pontos que exigem atenção

✅ Responder perguntas como:
- “Esse contrato tem renovação automática?”
- “Qual o prazo de vigência?”
- “Existe multa para cancelamento?”
- “Quais são os principais riscos?”
- “O que acontece se eu não cumprir?”

---

## O QUE VOCÊ NÃO PODE FAZER (OBRIGATÓRIO)

❌ NÃO afirmar que algo é ilegal
❌ NÃO dar parecer jurídico definitivo
❌ NÃO recomendar ações legais
❌ NÃO inventar informações que não estejam no texto
❌ NÃO assumir contexto externo ao contrato

Se algo não estiver claro no texto, diga explicitamente:
> “O contrato não deixa isso claro.”

---

## COMO RESPONDER (FORMATO PADRÃO)

Sempre que possível, siga esta estrutura:

### 📌 Resposta direta
Explique a resposta de forma clara e curta.

### 📄 Evidência no contrato
Cite o trecho relevante entre aspas.

### ⚠️ Ponto de atenção (se aplicável)
Explique riscos ou consequências práticas.

---

## EXEMPLOS DE RESPOSTA

**Pergunta:** Esse contrato tem renovação automática?

**Resposta:**
📌 **Resposta direta**  
Sim, o contrato prevê renovação automática.

📄 **Evidência no contrato**  
“Este contrato será renovado automaticamente por períodos iguais, salvo manifestação contrária de uma das partes com antecedência mínima de 30 dias.”

⚠️ **Ponto de atenção**  
Caso não haja aviso dentro do prazo, o contrato continuará vigente automaticamente.

---

## RESUMO EXECUTIVO (QUANDO SOLICITADO)

Quando solicitado um resumo do contrato, apresente:

- Tipo de contrato
- Prazo de vigência
- Principais obrigações
- Riscos relevantes
- Datas críticas

Use linguagem simples e tópicos curtos.

---

## AVISO FINAL (OBRIGATÓRIO EM TODA RESPOSTA)

Finalize sempre com:

> ⚠️ Esta análise é um apoio informativo e não substitui a avaliação de um advogado."

## 19.13 Telas
Objetivo do UX

Em 2 minutos o usuário consegue:
	1.	subir um contrato
	2.	ver vencimento + alertas sugeridos
	3.	obter um resumo claro e 2–3 riscos
	4.	criar tarefa “Revisar/renovar” automaticamente

⸻

UX do Módulo Contratos + IA

1) Tela: Lista de Contratos

Propósito: visão rápida do que está vencendo e do que é risco.

Elementos
	•	Header: Contratos
	•	Botão primário: Adicionar contrato
	•	Busca: “Buscar por nome, fornecedor, tag…”
	•	Filtros (chips):
	•	Status: Ativo | Vencendo | Vencido | Encerrado
	•	Tipo: Prestação | Aluguel | SaaS | Outro
	•	Tabela/lista com cards compactos:
	•	Nome do contrato
	•	Parte/fornecedor (se tiver)
	•	Vencimento (badge: verde/laranja/vermelho)
	•	Renovação automática (Sim/Não/Indefinido)
	•	Tags (ex: “SaaS”, “Fornecedor”)
	•	Ações rápidas: Ver, Perguntar à IA, Criar alerta

Microcopy (importante)
	•	Badge vencendo: “Vence em X dias”
	•	Badge vencido: “Vencido há X dias”

⸻

2) Tela: Adicionar Contrato (Wizard curto)

Propósito: reduzir fricção e evitar cadastro manual chato.

Passo 1 — Upload
	•	Upload PDF/DOCX (drag & drop)
	•	Campo opcional: Nome do contrato (autopreenche do arquivo)
	•	Checkbox: “Esse contrato tem dados sensíveis” (apenas para avisos)

Ao fazer upload: inicia análise automática (loading com progress)

Passo 2 — Extração IA (preview + confirmação)

Mostrar um painel com campos preenchidos pela IA (editáveis):
	•	Tipo do contrato (dropdown)
	•	Partes envolvidas (texto)
	•	Data de início
	•	Data de vencimento
	•	Renovação automática (Sim/Não/Indefinido)
	•	Prazo de aviso prévio (se houver)
	•	Multa de cancelamento (se houver)
	•	Valor e reajuste (se houver)

⚠️ Cada campo deve ter um botão “ver evidência” que abre o trecho do contrato (modal/drawer):
	•	“Extraído de: ‘…trecho…’”

Botão: Confirmar e salvar
Link secundário: “Salvar sem análise” (caso o OCR/texto falhe)

Passo 3 — Alertas

Checklist de alertas sugeridos:
	•	30 dias antes
	•	15 dias antes
	•	7 dias antes
	•	No dia
	•	Após vencimento (1 dia depois)

Botão: Salvar alertas

✅ Ao finalizar, oferecer:
	•	“Criar tarefa de revisão” (checkbox marcado por padrão)

⸻

3) Tela: Detalhe do Contrato

Propósito: clareza, risco e ação.

Layout (3 colunas ou 2 colunas)

Topo
	•	Título do contrato
	•	Status + vencimento (badge grande)
	•	Botões:
	•	Perguntar à IA
	•	Criar tarefa
	•	Configurar alertas
	•	Download / Ver documento

Seção A — Visão Geral (cards)
	•	Partes
	•	Vigência (início → fim)
	•	Renovação automática
	•	Aviso prévio
	•	Multa
	•	Valor/reajuste

Cada item com ícone “evidência” abrindo trecho do contrato.

Seção B — Resumo IA (curto e útil)

Card “Resumo executivo”
	•	5 bullets:
	•	tipo
	•	vigência
	•	obrigações principais
	•	como cancelar
	•	pontos críticos

Botão “Gerar novamente” (com throttle)

Seção C — Alertas e tarefas
	•	Lista de alertas configurados (editável)
	•	Tarefas vinculadas (kanban)
	•	“Revisar contrato X” (vence em 15 dias)
	•	“Negociar reajuste” etc.

Seção D — Histórico

Timeline:
	•	contrato enviado
	•	análise IA executada
	•	alertas criados
	•	tarefas criadas
	•	alterações

⸻

4) Tela: Assistente Jurídico IA (Chat focado)

Propósito: perguntas rápidas com evidência.

Componentes
	•	Campo de pergunta (input grande)
	•	Sugestões rápidas (chips):
	•	“Tem renovação automática?”
	•	“Qual multa por cancelamento?”
	•	“Qual prazo de aviso prévio?”
	•	“Quais os riscos principais?”
	•	“Resuma em 5 linhas”
	•	Resposta em 3 blocos:
	1.	Resposta direta
	2.	Evidência (trecho citado)
	3.	Ponto de atenção
	•	Aviso fixo no rodapé:
“Esta análise é informativa e não substitui um advogado.”

Regras de UX
	•	Sempre mostrar “fonte” (trecho do contrato)
	•	Botão “Ver no documento” que abre o PDF no trecho (se possível; senão abre página geral)

⸻

5) Tela: Alertas (Central)

Propósito: usuário ver o que vai vencer em breve e agir.
	•	Lista de alertas futuros agrupados por data
	•	Ações rápidas:
	•	“Criar tarefa agora”
	•	“Adiar alerta”
	•	“Marcar como resolvido”
	•	Integração com e-mail:
	•	“Notificar por e-mail o responsável”

⸻

Integrações com o resto do sistema (muito importante)
	•	Contratos podem ser criados via:
	•	Upload manual
	•	Automação de e-mail (se anexo PDF)
	•	Formulário externo (V2)
	•	Alertas sempre geram:
	•	tarefa no Kanban (opcional mas recomendado)
	•	notificação e-mail (opcional)

⸻

MVP enxuto (pra você lançar logo)

Inclui:
	•	Upload + extração IA + evidência
	•	Lista + detalhe
	•	Alertas + tarefa automática
	•	Chat IA com perguntas prontas

Não inclui (ainda):
	•	versão de contrato
	•	assinatura digital
	•	times/permissões avançadas
	•	OCR pesado (só se necessário)

---

## 19.14 Melhorias Futuras (Roadmap)

Esta seção documenta melhorias planejadas para implementação posterior ao MVP.

### 1. Extração de Texto de PDF (OCR)

**Objetivo**: Extrair automaticamente o texto de documentos PDF escaneados ou com texto não selecionável.

**Tecnologias sugeridas**:
- **Tesseract OCR** (open-source, multi-idioma)
- **Google Cloud Vision API** (pago, alta precisão)
- **Amazon Textract** (pago, específico para documentos)

**Implementação**:
```php
// Service: PdfTextExtractionService
- método: extractFromPdf(UploadedFile $file): string
- usar spatie/pdf-to-text para PDFs com texto selecionável
- fallback para OCR se texto não for detectado
- armazenar em contract_documents.extracted_text
```

**Integração**:
- Executar automaticamente após upload
- Exibir progresso no frontend
- Permitir re-processar manualmente

---

### 2. Viewer de PDF Embutido

**Objetivo**: Visualizar PDFs diretamente na interface, sem download, com destaque de trechos relevantes.

**Tecnologia**: PDF.js (Mozilla, open-source)

**Implementação**:
```vue
// Componente: PdfViewer.vue
- renderizar PDF página por página
- zoom, navegação, busca interna
- destacar trechos citados pela IA
- sincronizar scroll com citações
```

**Features**:
- Preview inline na tela de detalhes
- Botão "Ver no documento" que salta para página específica
- Anotações visuais (marcar cláusulas importantes)
- Download original

---

### 3. Assinatura Digital

**Objetivo**: Permitir assinatura eletrônica de contratos dentro da plataforma.

**Integrações sugeridas**:
- **DocuSign** (líder global)
- **ClickSign** (Brasil)
- **D4Sign** (Brasil)

**Fluxo**:
1. Usuário envia contrato para assinatura
2. Define signatários (e-mails)
3. Plataforma envia via API da integradora
4. Webhook notifica quando assinado
5. PDF assinado é anexado automaticamente

**Implementação**:
```php
// Service: DigitalSignatureService
- enviarParaAssinatura(Contract $contract, array $signatarios)
- webhookAssinaturaConcluida(array $payload)
- vincularPdfAssinado(Contract $contract, string $pdfUrl)
```

**Tabela adicional**: `contract_signatures`
- id, contract_id, signer_email, status, signed_at, external_id

---

### 4. Versionamento de Contratos

**Objetivo**: Manter histórico de versões (aditivos, renovações, alterações).

**Estrutura**:
```php
// Tabela: contract_versions
- id, contract_id, version_number, document_id
- changes_description, created_by, created_at
```

**Features**:
- Criar nova versão ao fazer upload de aditivo
- Comparar versões (diff visual)
- Histórico completo de alterações
- Restaurar versão anterior

**UX**:
- Timeline de versões na tela de detalhes
- Badge "Versão 2.0" ao lado do nome
- Botão "Ver mudanças" que mostra diff

---

### 5. Exportação de Relatórios

**Objetivo**: Gerar relatórios consolidados em Excel e PDF.

**Tipos de relatório**:

1. **Relatório de Vencimentos**
   - Contratos vencendo em X dias
   - Agrupado por mês
   - Gráficos de status

2. **Relatório Financeiro**
   - Soma de valores por tipo de contrato
   - Projeção de despesas mensais
   - Comparativo ano anterior

3. **Relatório de Compliance**
   - Contratos sem renovação definida
   - Alertas não tratados
   - Documentos faltantes

**Implementação**:
```php
// Service: ContractReportService
- exportToExcel(array $filters): BinaryFileResponse
- exportToPdf(array $filters): BinaryFileResponse
- generateDashboardData(): array
```

**Bibliotecas**:
- **Excel**: `maatwebsite/excel` (Laravel Excel)
- **PDF**: `barryvdh/laravel-dompdf` ou `spatie/browsershot`

**UX**:
- Botão "Exportar" na listagem
- Modal com opções de formato e filtros
- Download automático ou envio por e-mail

---

### 6. Dashboard de Contratos (Opcional)

**Objetivo**: Visão executiva consolidada.

**Widgets**:
- Total de contratos ativos
- Valor total em contratos
- Contratos vencendo (30, 15, 7 dias)
- Alertas pendentes
- Gráfico de vencimentos (próximos 6 meses)
- Top 5 fornecedores por valor

---

### 7. Notificações Multi-canal (Opcional)

**Objetivo**: Alertas além de e-mail.

**Canais**:
- E-mail (já implementado)
- WhatsApp (via Twilio/Evolution API)
- Slack (webhook)
- Push notification (navegador)

---

## 19.15 Priorização de Melhorias

**Implementar PRIMEIRO** (impacto alto, esforço médio):
1. ✅ Extração de texto de PDF (OCR)
2. ✅ Viewer de PDF embutido
3. ✅ Exportação de relatórios

**Implementar DEPOIS** (impacto alto, esforço alto):
4. Assinatura digital
5. Versionamento de contratos

**Implementar SE NECESSÁRIO** (impacto médio):
6. Dashboard de contratos
7. Notificações multi-canal

---

**Regra de Ouro**: Só adicionar feature após validação de uso real do MVP.

## Fim da tela de gestao de contrato
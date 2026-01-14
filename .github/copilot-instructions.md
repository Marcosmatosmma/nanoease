🧠 Guia Arquitetural Definitivo – Plataforma de Automação Operacional Inteligente

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

📌 Este arquivo deve ser lido antes de escrever código e respeitado durante toda a vida do projeto.
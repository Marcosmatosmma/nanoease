# 📋 Planejamento: Sistema de Classificação de E-mails

## 🎯 Visão Geral

Sistema híbrido que organiza e-mails no Gmail via labels/pastas E salva metadados no banco de dados para dashboards e análises.

**Arquitetura:** Híbrida (Gmail + BD de metadados)

**Objetivo:** Permitir que empresas classifiquem e-mails automaticamente (cobranças, notas fiscais, pedidos, etc) mantendo organização no Gmail mas com poder de analytics no sistema.

---

## 🗓️ Divisão em Fases

### **Fase 1 - MVP: Classificações + Integração Gmail** 
**Branch:** `feature/email-classification-mvp`  
**Prazo:** ~1 semana  
**Status:** 🔜 Pendente

**Objetivo:** Criar sistema básico de classificações que aplica labels no Gmail.

**Entregas:**
- ✅ Tabela `email_classifications` (catálogo de classificações)
- ✅ CRUD de classificações (criar, editar, listar)
- ✅ Interface `/classifications/settings` para gerenciar
- ✅ Ação "Organizar" na automação que aplica label no Gmail
- ✅ Integração com Gmail API para criar/aplicar labels
- ❌ **NÃO** salva metadados em BD ainda
- ❌ **NÃO** tem listas/dashboards ainda

**Arquivos a criar:**
```
Backend:
├── database/migrations/
│   └── xxxx_create_email_classifications_table.php
├── app/Domain/Classifications/
│   ├── Models/EmailClassification.php
│   ├── Controllers/ClassificationController.php
│   ├── Requests/StoreClassificationRequest.php
│   ├── Actions/CreateClassificationAction.php
│   ├── Actions/UpdateClassificationAction.php
│   └── Routes/webClassifications.php
├── app/Domain/Integrations/Services/
│   └── GmailLabelManager.php (criar/listar/aplicar labels)

Frontend:
├── resources/js/Pages/Classifications/
│   ├── Index.vue (lista classificações)
│   ├── Create.vue (criar nova)
│   └── Edit.vue (editar existente)
```

**Fluxo:**
```
1. Usuário cria classificação "Cobranças"
2. Sistema cria label "Financeiro/Cobranças" no Gmail
3. Usuário cria automação com ação "Organizar → Cobranças"
4. Quando e-mail chega, sistema aplica label no Gmail
5. E-mail fica organizado no Gmail ✅
```

---

### **Fase 2 - Listas e Metadados**
**Branch:** `feature/email-classification-lists`  
**Prazo:** ~1 semana  
**Status:** 🔜 Pendente

**Objetivo:** Salvar metadados de e-mails classificados e mostrar listas no sistema.

**Entregas:**
- ✅ Tabela `classified_emails` (metadados)
- ✅ Salvar registro quando classificar e-mail
- ✅ Tela `/classifications/{key}` mostrando lista de e-mails
- ✅ Botão "Ver no Gmail" que abre e-mail direto
- ✅ Filtros básicos (data, remetente)
- ✅ Paginação

**Arquivos a criar:**
```
Backend:
├── database/migrations/
│   └── xxxx_create_classified_emails_table.php
├── app/Domain/Classifications/
│   ├── Models/ClassifiedEmail.php
│   ├── Controllers/ClassifiedEmailController.php
│   └── Actions/StoreClassifiedEmailAction.php

Frontend:
├── resources/js/Pages/Classifications/
│   └── Show.vue (lista e-mails de uma classificação)
```

**Fluxo:**
```
1. Automação classifica e-mail
2. Sistema aplica label no Gmail
3. Sistema salva metadados em classified_emails:
   - email_message_id
   - email_from
   - email_subject
   - email_date
   - classification_id
4. Usuário acessa /classifications/cobrancas
5. Vê lista de e-mails com [Ver no Gmail]
```

---

### **Fase 3 - Analytics e IA**
**Branch:** `feature/email-classification-analytics`  
**Prazo:** ~1 semana  
**Status:** 🔜 Pendente

**Objetivo:** Dashboards, extração de dados com IA e relatórios.

**Entregas:**
- ✅ Dashboard `/classifications` com cards de resumo
- ✅ Extração de dados estruturados com IA (valores, datas, CNPJs)
- ✅ Campo `metadata` (JSON) na tabela `classified_emails`
- ✅ Filtros avançados (valor, status, vencimento)
- ✅ Gráficos (quantidade por mês, total de valores)
- ✅ Exportação CSV
- ✅ Ações em lote (marcar múltiplos como pago)

**Arquivos a criar:**
```
Backend:
├── app/Domain/AI/Services/
│   └── EmailDataExtractorService.php (extrai dados estruturados)
├── app/Domain/AI/Prompts/
│   └── EmailDataExtractionPrompt.php
├── app/Domain/Classifications/
│   ├── Controllers/ClassificationDashboardController.php
│   └── Actions/ExportClassifiedEmailsAction.php

Frontend:
├── resources/js/Pages/Classifications/
│   └── Dashboard.vue (visão geral com gráficos)
├── resources/js/components/
│   └── ClassificationCard.vue (card com estatísticas)
```

**Exemplo de metadata extraído:**
```json
{
  "tipo": "cobranca",
  "valor": 1500.00,
  "moeda": "BRL",
  "vencimento": "2026-01-20",
  "numero_documento": "1234",
  "cnpj": "12.345.678/0001-90",
  "status": "pendente",
  "banco": "Itaú",
  "codigo_barras": "12345678901234567890123456789012345678901234"
}
```

---

## 📊 Tabelas do Banco de Dados

### **Fase 1: email_classifications**
```sql
CREATE TABLE email_classifications (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,              -- "Cobranças"
    key VARCHAR(255) NOT NULL,               -- "cobrancas" (slug)
    gmail_label VARCHAR(255),                -- "Financeiro/Cobranças"
    color VARCHAR(7) DEFAULT '#3B82F6',      -- Cor do card/badge
    icon VARCHAR(255) DEFAULT 'lucide:tag',  -- Ícone
    description TEXT,
    active BOOLEAN DEFAULT true,
    position INT DEFAULT 0,                  -- Ordenação
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_key (user_id, key),
    INDEX idx_user_active (user_id, active)
);
```

### **Fase 2: classified_emails**
```sql
CREATE TABLE classified_emails (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    automation_id BIGINT,                    -- Qual automação classificou
    classification_id BIGINT NOT NULL,
    email_message_id VARCHAR(500) NOT NULL,  -- Message-ID do Gmail
    email_from VARCHAR(255),
    email_subject TEXT,
    email_date TIMESTAMP,
    gmail_label VARCHAR(255),                -- Label aplicado
    metadata JSON,                           -- Dados extras (Fase 3)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (automation_id) REFERENCES automations(id) ON DELETE SET NULL,
    FOREIGN KEY (classification_id) REFERENCES email_classifications(id) ON DELETE CASCADE,
    INDEX idx_user_classification (user_id, classification_id),
    INDEX idx_message_id (email_message_id),
    INDEX idx_email_date (email_date DESC)
);
```

---

## 🔄 Fluxo Completo (Todas as Fases)

### **1. Setup (Fase 1)**
```
Usuário → /classifications/settings → "Nova Classificação"
  Nome: Cobranças
  Label Gmail: Financeiro/Cobranças
  Cor: #EF4444 (vermelho)
  Ícone: dollar-sign
  
Sistema → Gmail API → Cria label "Financeiro/Cobranças"
Sistema → Salva em email_classifications
```

### **2. Automação (Fase 1)**
```
Usuário → /automations/new → E-mail recebido
  Condição: Assunto contém "fatura" OU "boleto"
  Ação: Organizar → Classificação "Cobranças"
  
Sistema → Salva automation com action_type='organizar'
```

### **3. Execução (Fase 1 + 2)**
```
Watcher → Detecta e-mail novo
  → EmailAutomationExecutor::executeClassify()
    → GmailLabelManager::applyLabel()  (Fase 1)
      → Gmail API aplica label
    → StoreClassifiedEmailAction::handle()  (Fase 2)
      → Salva em classified_emails
```

### **4. Visualização (Fase 2)**
```
Usuário → /classifications/cobrancas
  → Lista todos e-mails classificados
  → [Ver no Gmail] → Abre e-mail no Gmail
  → [Marcar como pago] → Atualiza metadata
```

### **5. Analytics (Fase 3)**
```
Usuário → /classifications
  → Dashboard com cards:
    📊 45 cobranças este mês
    💰 R$ 67.500,00 em aberto
    ✅ 12 pagas
    ⚠️ 5 vencidas
  → Gráfico de linha (cobranças por mês)
  → Exportar CSV
```

---

## 🎨 Protótipos de UI

### **Fase 1: /classifications/settings**
```
┌─────────────────────────────────────────────────────────┐
│ Classificações                          [+ Nova]        │
├─────────────────────────────────────────────────────────┤
│                                                          │
│ ┌────────────────────────────────────────────────────┐ │
│ │ 💰 Cobranças                         [Editar] [X] │ │
│ │ Label: Financeiro/Cobranças                        │ │
│ │ Descrição: E-mails de faturas e boletos           │ │
│ └────────────────────────────────────────────────────┘ │
│                                                          │
│ ┌────────────────────────────────────────────────────┐ │
│ │ 📄 Notas Fiscais                    [Editar] [X] │ │
│ │ Label: Financeiro/Notas Fiscais                   │ │
│ │ Descrição: NF-e e documentos fiscais              │ │
│ └────────────────────────────────────────────────────┘ │
│                                                          │
│ ┌────────────────────────────────────────────────────┐ │
│ │ 📦 Pedidos                          [Editar] [X] │ │
│ │ Label: Vendas/Pedidos                             │ │
│ │ Descrição: Confirmações de pedidos                │ │
│ └────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

### **Fase 2: /classifications/cobrancas**
```
┌─────────────────────────────────────────────────────────┐
│ ← Voltar       💰 Cobranças (45)                        │
├─────────────────────────────────────────────────────────┤
│ Filtros: [Todos ▼] [Este mês ▼] [Buscar...]          │
├─────────────────────────────────────────────────────────┤
│                                                          │
│ ┌────────────────────────────────────────────────────┐ │
│ │ cliente@empresa.com                  15/01 14:30  │ │
│ │ Fatura #1234 - Vencimento 20/01                   │ │
│ │ [Ver no Gmail]                                     │ │
│ └────────────────────────────────────────────────────┘ │
│                                                          │
│ ┌────────────────────────────────────────────────────┐ │
│ │ fornecedor@loja.com                  14/01 09:15  │ │
│ │ Boleto referente pedido #5678                     │ │
│ │ [Ver no Gmail]                                     │ │
│ └────────────────────────────────────────────────────┘ │
│                                                          │
│ Página 1 de 3                              [1] 2 3 →   │
└─────────────────────────────────────────────────────────┘
```

### **Fase 3: /classifications (Dashboard)**
```
┌─────────────────────────────────────────────────────────┐
│ Dashboard de Classificações                              │
├─────────────────────────────────────────────────────────┤
│ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐  │
│ │💰 Cobr.  │ │📄 NF-e   │ │📦 Pedidos│ │📧 Outros │  │
│ │   45     │ │   12     │ │   28     │ │   15     │  │
│ │ +5 hoje  │ │ +2 hoje  │ │ +8 hoje  │ │ +1 hoje  │  │
│ └──────────┘ └──────────┘ └──────────┘ └──────────┘  │
├─────────────────────────────────────────────────────────┤
│                                                          │
│ 📊 Cobranças nos últimos 6 meses                        │
│ ┌────────────────────────────────────────────────────┐ │
│ │     ╭─╮                                            │ │
│ │     │ │           ╭─╮                              │ │
│ │ ╭─╮ │ │     ╭─╮   │ │         ╭─╮                 │ │
│ │ │ │ │ │ ╭─╮ │ │   │ │   ╭─╮   │ │                 │ │
│ │ │ │ │ │ │ │ │ │   │ │   │ │   │ │                 │ │
│ │ Aug Sep Oct Nov Dec Jan                            │ │
│ └────────────────────────────────────────────────────┘ │
│                                                          │
│ 💰 Total em aberto: R$ 67.500,00                        │
│ ✅ Pagas: R$ 12.300,00                                  │
│ ⚠️ Vencidas: 5 (R$ 8.900,00)                            │
│                                                          │
│ [Exportar CSV] [Gerar Relatório]                       │
└─────────────────────────────────────────────────────────┘
```

---

## 🚀 Ordem de Implementação

### **Agora (Sessão Atual):**
1. ✅ Criar branch `feature/email-classification-mvp`
2. ✅ Criar este documento de planejamento
3. ⏸️ **Parar aqui** - Aguardar aprovação

### **Próxima Sessão:**
1. 🔜 Implementar Fase 1 completa
2. 🔜 Testar integração com Gmail
3. 🔜 Commit e merge na main

### **Sessões Futuras:**
1. 🔜 Fase 2 (nova branch)
2. 🔜 Fase 3 (nova branch)

---

## 📝 Notas Importantes

### **Integração Gmail API:**
- Precisamos de escopo adicional: `https://www.googleapis.com/auth/gmail.labels`
- Método `users.labels.create` para criar labels
- Método `users.messages.modify` para aplicar labels

### **Performance:**
- Índices bem definidos em `classified_emails`
- Paginação obrigatória nas listas
- Cache de contadores no dashboard (Fase 3)

### **Segurança:**
- Validar que usuário só acessa suas próprias classificações
- Rate limiting na API do Gmail
- Sanitização de nomes de labels

---

## ✅ Checklist de Cada Fase

### **Fase 1:**
- [ ] Migration `email_classifications`
- [ ] Model `EmailClassification`
- [ ] Controller CRUD
- [ ] Routes
- [ ] Service `GmailLabelManager`
- [ ] Páginas Vue (Index, Create, Edit)
- [ ] Atualizar `EmailAutomationExecutor::executeClassify()`
- [ ] Testes manuais
- [ ] Documentação atualizada

### **Fase 2:**
- [ ] Migration `classified_emails`
- [ ] Model `ClassifiedEmail`
- [ ] Controller Show
- [ ] Action `StoreClassifiedEmailAction`
- [ ] Página Vue (Show.vue)
- [ ] Integração com watcher
- [ ] Testes manuais
- [ ] Documentação atualizada

### **Fase 3:**
- [ ] Service `EmailDataExtractorService`
- [ ] Prompt `EmailDataExtractionPrompt`
- [ ] Controller Dashboard
- [ ] Action Export CSV
- [ ] Página Vue (Dashboard.vue)
- [ ] Componente `ClassificationCard.vue`
- [ ] Gráficos (Chart.js ou similar)
- [ ] Testes manuais
- [ ] Documentação atualizada

---

**Criado em:** 2026-01-15  
**Autor:** NanoEase Team  
**Versão:** 1.0  
**Status:** 📋 Planejamento

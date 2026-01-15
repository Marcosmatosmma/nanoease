# 📋 Fase 1 - MVP: Classificações + Integração Gmail

**Branch:** `feature/email-classification-mvp`  
**Data Início:** 2026-01-15  
**Status:** 🚧 Em Progresso

---

## 🎯 Objetivo

Criar sistema básico de classificações que permite usuários criarem categorias personalizadas (Cobranças, Notas Fiscais, Pedidos, etc) e automaticamente aplicar labels no Gmail quando e-mails forem classificados.

---

## ✅ Checklist de Implementação

### **Backend:**

- [ ] **Migration: `email_classifications`**
  - [ ] Criar tabela com campos: id, user_id, name, key, gmail_label, color, icon, description, active, position
  - [ ] Adicionar foreign keys e índices

- [ ] **Model: `EmailClassification`**
  - [ ] Relacionamento com User
  - [ ] Scopes (active, byUser)
  - [ ] Accessors e Mutators
  - [ ] Casting de campos

- [ ] **Service: `GmailLabelManager`**
  - [ ] Método `createLabel()` - Criar label no Gmail
  - [ ] Método `listLabels()` - Listar labels existentes
  - [ ] Método `applyLabel()` - Aplicar label em e-mail
  - [ ] Método `removeLabel()` - Remover label de e-mail
  - [ ] Tratamento de erros da API

- [ ] **Request: `StoreClassificationRequest`**
  - [ ] Validação de name (required, max:255)
  - [ ] Validação de gmail_label (optional, max:255)
  - [ ] Validação de color (hex color)
  - [ ] Validação de icon (string)

- [ ] **Request: `UpdateClassificationRequest`**
  - [ ] Similar ao Store
  - [ ] Validar que key não pode mudar

- [ ] **Actions:**
  - [ ] `CreateClassificationAction` - Criar classificação + label no Gmail
  - [ ] `UpdateClassificationAction` - Atualizar classificação
  - [ ] `DeleteClassificationAction` - Remover classificação (verificar se tem automações usando)

- [ ] **Controller: `ClassificationController`**
  - [ ] `index()` - Listar classificações do usuário
  - [ ] `create()` - Renderizar formulário
  - [ ] `store()` - Criar nova classificação
  - [ ] `edit()` - Renderizar formulário de edição
  - [ ] `update()` - Atualizar classificação
  - [ ] `destroy()` - Remover classificação

- [ ] **Routes: `webClassifications.php`**
  - [ ] GET `/classifications`
  - [ ] GET `/classifications/create`
  - [ ] POST `/classifications`
  - [ ] GET `/classifications/{id}/edit`
  - [ ] PATCH `/classifications/{id}`
  - [ ] DELETE `/classifications/{id}`

- [ ] **Atualizar: `EmailAutomationExecutor`**
  - [ ] Método `executeClassify()` implementado
  - [ ] Buscar classification_id do action_config
  - [ ] Chamar `GmailLabelManager::applyLabel()`
  - [ ] Retornar resultado estruturado

- [ ] **Atualizar: `StoreEmailAutomationRequest`**
  - [ ] Validação de `action_config.classification_id` quando action_type='organizar'

### **Frontend:**

- [ ] **Page: `Classifications/Index.vue`**
  - [ ] Lista de classificações em cards
  - [ ] Botão "Nova Classificação"
  - [ ] Ações: Editar, Excluir
  - [ ] Empty state quando não tem classificações
  - [ ] Loading states

- [ ] **Page: `Classifications/Create.vue`**
  - [ ] Formulário com campos:
    - [ ] Nome (input)
    - [ ] Label Gmail (input, opcional)
    - [ ] Cor (color picker)
    - [ ] Ícone (dropdown com ícones)
    - [ ] Descrição (textarea)
  - [ ] Botão Salvar
  - [ ] Botão Cancelar
  - [ ] Validação frontend

- [ ] **Page: `Classifications/Edit.vue`**
  - [ ] Similar ao Create
  - [ ] Pré-preencher campos
  - [ ] Botão Excluir (com confirmação)

- [ ] **Atualizar: `Automations/EmailReceived.vue`**
  - [ ] Quando action_type='organizar':
    - [ ] Dropdown para selecionar classificação
    - [ ] Botão "Criar nova classificação" (link para create)

---

## 📊 Schema do Banco de Dados

```sql
CREATE TABLE email_classifications (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    key VARCHAR(255) NOT NULL,
    gmail_label VARCHAR(255),
    color VARCHAR(7) DEFAULT '#3B82F6',
    icon VARCHAR(255) DEFAULT 'lucide:tag',
    description TEXT,
    active BOOLEAN DEFAULT true,
    position INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_key (user_id, key),
    INDEX idx_user_active (user_id, active)
);
```

---

## 🔄 Fluxo de Uso

### **1. Criar Classificação**
```
Usuário → /classifications → "Nova Classificação"
  Nome: Cobranças
  Label Gmail: Financeiro/Cobranças
  Cor: #EF4444
  Ícone: dollar-sign
  Descrição: E-mails de faturas e boletos
  
[Salvar]

Backend:
  1. Valida dados
  2. Gera key: "cobrancas" (slug do nome)
  3. Chama GmailLabelManager::createLabel("Financeiro/Cobranças")
  4. Gmail API cria label
  5. Salva em email_classifications
  6. Retorna sucesso
```

### **2. Criar Automação**
```
Usuário → /automations/new → E-mail recebido
  Condição: Assunto contém "fatura"
  Ação: Organizar e classificar
  Classificação: Cobranças ▼
  
[Salvar]

Backend:
  1. Salva automation
  2. Salva action com type='organizar' e config={ classification_id: 1 }
```

### **3. Executar Automação**
```
Watcher detecta e-mail novo
  Subject: "Fatura #1234"
  
EmailAutomationExecutor::execute()
  → Decisão: executar
  → executeClassify()
    → Busca classification (id=1, name="Cobranças", gmail_label="Financeiro/Cobranças")
    → GmailLabelManager::applyLabel(messageId, "Financeiro/Cobranças")
      → Gmail API adiciona label ao e-mail
    → Retorna { status: 'executed', message: 'E-mail classificado' }
```

### **4. Verificar no Gmail**
```
Usuário abre Gmail
  → Vê e-mail com label "Financeiro/Cobranças"
  → E-mail está na pasta "Financeiro" > "Cobranças"
```

---

## 🎨 Protótipos de UI

### **Tela: /classifications**
```
┌─────────────────────────────────────────────────────────┐
│ Classificações                    [+ Nova Classificação]│
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

### **Tela: /classifications/create**
```
┌─────────────────────────────────────────────────────────┐
│ ← Voltar          Nova Classificação                    │
├─────────────────────────────────────────────────────────┤
│                                                          │
│ Nome *                                                   │
│ ┌────────────────────────────────────────────────────┐ │
│ │ Cobranças                                          │ │
│ └────────────────────────────────────────────────────┘ │
│                                                          │
│ Label no Gmail                                           │
│ ┌────────────────────────────────────────────────────┐ │
│ │ Financeiro/Cobranças                               │ │
│ └────────────────────────────────────────────────────┘ │
│ 💡 Se deixar vazio, usaremos o nome acima              │
│                                                          │
│ Cor                         Ícone                        │
│ ┌──────────┐               ┌──────────────────────────┐│
│ │ #EF4444  │               │ 💰 dollar-sign        ▼ ││
│ └──────────┘               └──────────────────────────┘│
│                                                          │
│ Descrição                                                │
│ ┌────────────────────────────────────────────────────┐ │
│ │ E-mails de faturas e boletos                       │ │
│ │                                                    │ │
│ └────────────────────────────────────────────────────┘ │
│                                                          │
│                              [Cancelar] [Criar]         │
└─────────────────────────────────────────────────────────┘
```

### **Tela: /automations/email-received (action=organizar)**
```
┌─────────────────────────────────────────────────────────┐
│ 4) Escolha a ação                                        │
│                                                          │
│ [✓] Organizar e classificar                             │
│                                                          │
├─────────────────────────────────────────────────────────┤
│                                                          │
│ 5) Selecione a classificação                            │
│                                                          │
│ ┌────────────────────────────────────────────────────┐ │
│ │ 💰 Cobranças                                    ▼ │ │
│ └────────────────────────────────────────────────────┘ │
│                                                          │
│ Ou [+ Criar nova classificação]                         │
│                                                          │
│ 💡 O e-mail será marcado com a label                    │
│    "Financeiro/Cobranças" no Gmail                      │
└─────────────────────────────────────────────────────────┘
```

---

## 🧪 Testes Manuais

### **Teste 1: Criar Classificação**
- [ ] Acessar `/classifications`
- [ ] Clicar "Nova Classificação"
- [ ] Preencher formulário
- [ ] Salvar
- [ ] Verificar se aparece na lista
- [ ] Abrir Gmail e verificar se label foi criado

### **Teste 2: Editar Classificação**
- [ ] Clicar "Editar" em uma classificação
- [ ] Alterar nome e cor
- [ ] Salvar
- [ ] Verificar mudanças

### **Teste 3: Excluir Classificação**
- [ ] Clicar "Excluir" (X)
- [ ] Confirmar exclusão
- [ ] Verificar se sumiu da lista
- [ ] Verificar se label foi removido do Gmail (opcional)

### **Teste 4: Criar Automação com Classificação**
- [ ] Criar nova automação
- [ ] Escolher ação "Organizar"
- [ ] Selecionar classificação "Cobranças"
- [ ] Salvar e ativar

### **Teste 5: Executar Automação**
- [ ] Iniciar watcher: `php artisan automations:watch`
- [ ] Enviar e-mail de teste que match a regra
- [ ] Verificar no console se classificou
- [ ] Abrir Gmail e verificar se label foi aplicado

---

## 🚧 O que NÃO está nesta fase

- ❌ Salvar metadados em BD (Fase 2)
- ❌ Listas de e-mails classificados (Fase 2)
- ❌ Dashboard com estatísticas (Fase 3)
- ❌ Extração de dados com IA (Fase 3)
- ❌ Exportação CSV (Fase 3)
- ❌ Filtros avançados (Fase 3)

---

## 📝 Notas de Implementação

### **Gmail API - Criar Label:**
```php
POST https://gmail.googleapis.com/gmail/v1/users/me/labels
{
  "name": "Financeiro/Cobranças",
  "labelListVisibility": "labelShow",
  "messageListVisibility": "show"
}
```

### **Gmail API - Aplicar Label:**
```php
POST https://gmail.googleapis.com/gmail/v1/users/me/messages/{messageId}/modify
{
  "addLabelIds": ["Label_123"]
}
```

### **Estrutura de Labels Hierárquica:**
```
Financeiro/
  ├─ Cobranças
  ├─ Notas Fiscais
  └─ Pagamentos

Vendas/
  ├─ Pedidos
  └─ Orçamentos
```

Usar `/` no nome do label cria hierarquia no Gmail.

---

## 🔄 Próximos Passos

Após completar Fase 1:
1. ✅ Commit e push
2. ✅ Merge na main (ou staging)
3. ✅ Criar branch `feature/email-classification-lists` para Fase 2
4. ✅ Implementar Fase 2

---

**Criado em:** 2026-01-15  
**Última atualização:** 2026-01-15  
**Status:** 🚧 Em progresso

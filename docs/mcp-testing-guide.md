# 🧪 Guia Rápido de Teste - Sistema MCP

## Pré-requisitos

1. Ter uma automação criada no banco (tabela `automations`)
2. Ter o Prism configurado
3. Ter uma integração Gmail configurada (opcional, para testes com e-mails reais)

## Cenário de Teste 1: Teste Simples

### Regra de Exemplo (salva no banco)
```
rule_text: "se chegar email de marcos.araujo@supleti.com"
plan_rule_text: "Encaminhar todos os e-mails recebidos de marcos.araujo@supleti.com para marcosmatosaraujo@gmail.com"
```

### Ação Configurada
```json
{
  "type": "encaminhar",
  "config": {
    "forward_to": ["marcosmatosaraujo@gmail.com"]
  }
}
```

### Comando de Teste (Modo Antigo)
```bash
php artisan automations:test 1 \
  --from="marcos.araujo@supleti.com" \
  --subject="Relatório Mensal" \
  --body="Segue o relatório do mês."
```

**Resultado Esperado:**
- Status: `executed` ou `ignored`
- Mostra apenas se deve executar ou não
- Usa o `RuleInterpreter` antigo

### Comando de Teste (Modo MCP) 🧠
```bash
php artisan automations:test 1 \
  --use-mcp \
  --from="marcos.araujo@supleti.com" \
  --subject="Relatório Mensal" \
  --body="Segue o relatório do mês."
```

**Resultado Esperado:**
- Status: `executed`
- Mostra a ação que será executada: `encaminhar`
- Mostra o raciocínio da IA
- Mostra nível de confiança
- Mostra condições atendidas

---

## Cenário de Teste 2: Buscar E-mails Reais

### Pré-requisito
- Integração Gmail configurada e funcionando

### Comando (apenas análise, sem executar)
```bash
php artisan automations:test 1 \
  --use-mcp \
  --search="from:marcos.araujo@supleti.com" \
  --limit=5
```

**O que acontece:**
1. Busca os últimos 5 e-mails de `marcos.araujo@supleti.com` no Gmail
2. Para cada e-mail, o MCP decide se deve executar
3. Mostra uma tabela com:
   - ID do e-mail
   - Remetente
   - Assunto
   - Decisão (executar/ignorar)
   - Nível de confiança
   - Ação a ser executada
   - Raciocínio da IA

**Nenhum e-mail é enviado** (modo análise)

---

## Cenário de Teste 3: Executar de Verdade

### ⚠️ ATENÇÃO: Este comando ENVIA e-mails de verdade!

```bash
php artisan automations:test 1 \
  --use-mcp \
  --search="from:marcos.araujo@supleti.com" \
  --limit=3 \
  --execute
```

**O que acontece:**
1. Busca 3 e-mails
2. O MCP decide quais executar
3. **ENVIA** os e-mails para os destinatários configurados
4. Mostra status de envio

---

## Comparação: Antigo vs MCP

### Sistema Antigo (RuleInterpreter)

**Resposta da IA:**
```json
{
  "should_execute": true,
  "confidence": 0.85,
  "reason": "matched_sender",
  "matched_sender": true,
  "matched_action": false
}
```

**Limitações:**
- Não sabe QUAL ação executar
- Não extrai parâmetros dinâmicos
- Decisão binária (sim/não)
- Pouco contexto sobre o raciocínio

### Sistema MCP (ActionDecisionEngine)

**Resposta da IA:**
```json
{
  "should_execute": true,
  "confidence": 0.95,
  "matched_conditions": [
    "Remetente é marcos.araujo@supleti.com",
    "Regra especifica encaminhar para marcosmatosaraujo@gmail.com"
  ],
  "action_to_execute": "encaminhar",
  "action_parameters": {
    "forward_to": ["marcosmatosaraujo@gmail.com"],
    "preserve_original": true,
    "add_note": "Encaminhado automaticamente pela automação"
  },
  "reasoning": "O e-mail é do remetente especificado na regra e deve ser encaminhado para o endereço configurado."
}
```

**Vantagens:**
- ✅ Sabe exatamente qual ação executar
- ✅ Extrai e valida parâmetros
- ✅ Raciocínio detalhado
- ✅ Condições explícitas atendidas
- ✅ Pode adicionar notas/contexto
- ✅ Preparado para múltiplas ações

---

## Debug e Troubleshooting

### Ver a resposta bruta do Prism

Adicione no `AutomationExecutor.php` após a decisão:

```php
// Temporário para debug
\Log::info('MCP Decision', [
    'decision' => $decision,
    'raw_response' => $decision['raw_response'] ?? null,
]);
```

### Testar o Prompt Diretamente

Crie um teste artisan temporário:

```php
php artisan tinker

$engine = app(\App\Domain\AI\Services\ActionDecisionEngine::class);

$result = $engine->decide(
    'Encaminhar e-mails de marcos.araujo@supleti.com para marcosmatosaraujo@gmail.com',
    [
        'from' => 'marcos.araujo@supleti.com',
        'subject' => 'Teste',
        'body' => 'Corpo do teste'
    ],
    [
        ['type' => 'encaminhar', 'config' => ['forward_to' => ['marcosmatosaraujo@gmail.com']]]
    ]
);

dd($result);
```

---

## Próximos Testes Recomendados

### 1. Teste de Confiança Baixa
- E-mail de remetente diferente do especificado
- Esperado: `should_execute: false`, confidence baixa

### 2. Teste de Resposta Automática
- Criar regra: "Responder 'Recebido!' para e-mails de suporte@example.com"
- Configurar ação `responder`
- Testar com `--use-mcp`

### 3. Teste de Múltiplas Condições
- Regra: "Se e-mail de marcos.araujo@supleti.com COM assunto contendo 'urgente'"
- Testar com assuntos variados

### 4. Teste de Extração de Parâmetros
- Regra: "Encaminhar e-mails de orçamento para financeiro@empresa.com"
- Verificar se a IA extrai o destinatário correto

---

## Checklist de Validação

- [ ] MCP decide corretamente quando executar
- [ ] Nível de confiança é coerente
- [ ] Ação correta é selecionada
- [ ] Parâmetros são extraídos corretamente
- [ ] Raciocínio é claro e útil
- [ ] Condições atendidas são listadas
- [ ] E-mails são enviados para destinatários corretos
- [ ] Logs estão sendo registrados
- [ ] Erros são tratados com fallback

---

## 🎯 Sucesso!

Se todos os testes passarem, o sistema MCP está funcionando e você pode:

1. Integrar no fluxo real de recebimento de e-mails
2. Adicionar mais tipos de ações
3. Implementar o registro de execuções
4. Criar interface de visualização das decisões

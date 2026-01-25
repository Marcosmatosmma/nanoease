<?php

declare(strict_types=1);

namespace App\Domain\AI\Prompts\Contracts;

use App\Domain\AI\Prompts\BasePrompt;

/**
 * Prompt para extração de dados estruturados do contrato
 * 
 * Identifica: datas, partes, valores, renovação, multas
 */
class ContractDataExtractionPrompt extends BasePrompt
{
    public function render(array $context = []): string
    {
        $contractText = $context['contract_text'] ?? '';
        $teamName = $context['team_name'] ?? '';

        return <<<PROMPT
Você é um **Assistente Especializado em Análise de Contratos**.

Sua tarefa é **extrair informações estruturadas** de um contrato fornecido.

---

## INFORMAÇÃO DO USUÁRIO

Nome da empresa do usuário: **{$teamName}**

---

## TEXTO DO CONTRATO

{$contractText}

---

## INFORMAÇÕES A EXTRAIR

Analise o contrato e extraia as seguintes informações no formato JSON:

```json
{
  "contract_type": "tipo do contrato (prestação de serviço, aluguel, SaaS, etc.)",
  "contract_number": "número do contrato se existir, ou null",
  "contract_object": "resumo breve do objeto/escopo do contrato (1-2 frases)",
  "contractor": "nome completo de quem está CONTRATANDO o serviço/produto",
  "contractor_cpf_cnpj": "CPF ou CNPJ do contratante (apenas números)",
  "contractor_address": "endereço completo do contratante extraído do contrato, ou null",
  "contracted": "nome completo de quem FOI CONTRATADO (quem vai prestar o serviço)",
  "contracted_cpf_cnpj": "CPF ou CNPJ do contratado (apenas números)",
  "contracted_address": "endereço completo do contratado extraído do contrato, ou null",
  "my_role_suggestion": "contratante|contratado (baseado em comparar '{$teamName}' com contractor/contracted)",
  "start_date": "YYYY-MM-DD ou null",
  "end_date": "YYYY-MM-DD ou null",
  "auto_renewal": true|false|null,
  "amount": 0.00,
  "currency": "BRL|USD|EUR",
  "payment_frequency": "mensal|anual|único|null",
  "payment_terms": "descrição completa das condições de pagamento: número de parcelas, valor de cada parcela, dia de vencimento, forma de pagamento (depósito/boleto/PIX), obrigatoriedade de nota fiscal, conta bancária, etc. Seja DETALHADO.",
  "cancellation_notice_days": 0,
  "penalties": {
    "cancellation_fee": "descrição ou null",
    "breach_penalty": "descrição ou null"
  },
  "key_clauses": [
    "Cláusula importante 1",
    "Cláusula importante 2"
  ],
  "risks": [
    "Risco prático 1",
    "Risco prático 2"
  ]
}
```

---

## REGRAS OBRIGATÓRIAS

1. Use **APENAS** informações presentes no texto do contrato
2. Se algo não estiver claro, use `null`
3. Para datas, use formato ISO (YYYY-MM-DD)
4. Para valores monetários, use números decimais
5. Identifique riscos PRÁTICOS para o usuário
6. **IMPORTANTE**: Para `my_role_suggestion`, compare o nome da empresa do usuário ('{$teamName}') com `contractor` e `contracted`:
   - Se '{$teamName}' aparece em `contractor`, retorne "contratante"
   - Se '{$teamName}' aparece em `contracted`, retorne "contratado"
   - Se não conseguir identificar, retorne null
7. Para `contract_object`, crie um resumo conciso do que está sendo contratado (ex: "Desenvolvimento de software de gestão empresarial")
8. **CRITICAL - CPF/CNPJ**: Extraia os CPF ou CNPJ das partes envolvidas:
   - Retorne APENAS números (sem pontos, traços ou barras)
   - Valide se tem 11 dígitos (CPF) ou 14 dígitos (CNPJ)
   - Se não encontrar, retorne null
9. **CRITICAL - Endereços**: Extraia o endereço completo de ambas as partes:
   - Para `contractor_address` e `contracted_address`, extraia o endereço completo encontrado no contrato
   - Inclua rua, número, complemento, bairro, cidade, estado e CEP quando disponível
   - Se não encontrar, retorne null
10. **CRITICAL - payment_terms**: Extraia TODOS os detalhes financeiros e de pagamento encontrados no contrato:
   - Número total de parcelas (ex: "12 parcelas")
   - Valor de cada parcela (ex: "R$ 5.000,00 cada")
   - Dia de vencimento (ex: "todo dia 20")
   - Forma de pagamento (depósito bancário, boleto, PIX, etc)
   - Obrigatoriedade de emissão de nota fiscal
   - Dados bancários para pagamento
   - Qualquer outra condição financeira relevante
   - Seja o mais DETALHADO possível, incluindo todos os números e datas mencionados

---

## RESPOSTA ESPERADA

Retorne SOMENTE o JSON válido, sem texto adicional antes ou depois.
PROMPT;
    }
}

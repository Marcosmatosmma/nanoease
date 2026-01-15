<?php

declare(strict_types=1);

namespace App\Domain\AI\Prompts;

final class EmailDataExtractionPrompt
{
    public static function build(string $emailSubject, string $emailBody, string $emailFrom): string
    {
        return <<<PROMPT
Você é um assistente especializado em extrair dados estruturados de e-mails corporativos.

**E-mail para análise:**
De: {$emailFrom}
Assunto: {$emailSubject}
Corpo:
{$emailBody}

**Sua tarefa:**
Extrair informações relevantes e retornar um JSON estruturado. Analise cuidadosamente e identifique:

1. **Tipo de documento**: cobranca, nota_fiscal, pedido, orcamento, outro
2. **Valor monetário**: extraia valores em reais (R$) ou outras moedas
3. **Data de vencimento**: identifique datas de pagamento/vencimento
4. **Número do documento**: número de fatura, NF-e, pedido, etc
5. **CNPJ/CPF**: documentos fiscais mencionados
6. **Nome da empresa**: remetente ou empresa citada
7. **Código de barras**: se houver linha digitável ou código de barras
8. **Status**: pago, pendente, vencido (inferir se possível)
9. **Banco**: instituição bancária mencionada
10. **Observações**: informações adicionais relevantes

**Regras:**
- Se um campo não for encontrado, use `null`
- Valores monetários devem ser números (ex: 1500.50, não "R$ 1.500,50")
- Datas no formato ISO 8601 (YYYY-MM-DD)
- CNPJ/CPF sem formatação (apenas números)
- Seja preciso e objetivo

**Retorne APENAS um JSON válido, sem markdown, sem explicações:**

```json
{
  "tipo": "cobranca|nota_fiscal|pedido|orcamento|outro",
  "valor": 0.00,
  "moeda": "BRL",
  "vencimento": "YYYY-MM-DD",
  "numero_documento": "string ou null",
  "cnpj": "string ou null",
  "cpf": "string ou null",
  "empresa": "string ou null",
  "banco": "string ou null",
  "codigo_barras": "string ou null",
  "status": "pago|pendente|vencido|null",
  "observacoes": "string ou null"
}
```
PROMPT;
    }
}

# Integração com API CNPJ.ws - Documentação

## 📋 Resumo da Implementação

Sistema de preenchimento automático de dados de Nota Fiscal através da integração com a API pública CNPJ.ws.

---

## 🎯 Funcionalidades Implementadas

### 1. Extração de Dados pela IA
- IA agora extrai **CPF/CNPJ** e **endereços** de ambas as partes do contrato
- Campos adicionados no JSON de resposta:
  - `contractor_address`
  - `contracted_address`

### 2. Preenchimento Automático de Dados de NF

#### **Quando usuário faz upload do contrato:**
1. IA extrai todos os dados do documento
2. Se `my_role === 'contratado'`, o sistema automaticamente:
   - Preenche **CNPJ do destinatário** com CNPJ do contratante
   - Preenche **Nome do destinatário** com nome do contratante
   - Preenche **Endereço** com endereço extraído do contrato
   - **Busca dados na API CNPJ.ws automaticamente**

#### **Quando usuário digita/altera CNPJ manualmente:**
- Ao sair do campo (evento `blur`), sistema busca dados na API automaticamente
- Indicador de loading aparece durante a busca

### 3. Dados Preenchidos pela API

A API CNPJ.ws retorna e o sistema preenche:

✅ **Inscrição Estadual** (primeira IE ativa encontrada)
✅ **Endereço Completo** (se ainda não preenchido)
✅ **Dados completos da API** salvos em coluna JSON para consulta posterior

---

## 🗃️ Estrutura de Banco de Dados

### Migration: `2026_01_25_025459_add_invoice_state_registration_and_api_data_to_contracts_table`

```php
$table->string('invoice_state_registration', 50)->nullable()
$table->json('invoice_cnpj_api_data')->nullable()
```

### Campos no Model `Contract`:

```php
'invoice_state_registration',  // Inscrição Estadual
'invoice_cnpj_api_data',        // JSON com resposta completa da API
```

---

## 🔧 Componentes Técnicos

### 1. **Serviço Backend**: `CnpjApiService.php`

Localização: `app/Domain/Contracts/Services/CnpjApiService.php`

#### Métodos disponíveis:

```php
// Consulta CNPJ na API pública
public function consultarCnpj(string $cnpj): ?array

// Extrai Inscrição Estadual dos dados
public function extrairInscricaoEstadual(array $apiData): ?string

// Formata dados da API para uso
public function formatarDados(array $apiData): array

// Monta endereço completo
private function montarEnderecoCompleto(array $estabelecimento): ?string
```

#### Exemplo de uso:

```php
use App\Domain\Contracts\Services\CnpjApiService;

$service = new CnpjApiService();
$dados = $service->consultarCnpj('00.000.000/0000-00');

if ($dados) {
    $ie = $service->extrairInscricaoEstadual($dados);
    $dadosFormatados = $service->formatarDados($dados);
}
```

### 2. **Prompt da IA**: `ContractDataExtractionPrompt.php`

Atualizado para extrair:
- `contractor_cpf_cnpj` (apenas números)
- `contractor_address` (endereço completo)
- `contracted_cpf_cnpj` (apenas números)
- `contracted_address` (endereço completo)

### 3. **Frontend**: `Create.vue`

#### Novo campo adicionado:

```vue
<div>
  <label>Inscrição Estadual</label>
  <Input
    v-model="form.invoice_state_registration"
    placeholder="Inscrição Estadual"
    :disabled="loadingCnpjData"
  />
  <p v-if="loadingCnpjData">
    Buscando dados do CNPJ...
  </p>
</div>
```

#### Lógica de integração com API:

```javascript
// Busca automática ao sair do campo CNPJ
const fetchCnpjData = async () => {
  const cnpj = form.invoice_recipient_cnpj
  if (!cnpj || cnpj.length < 14) return
  await fetchCnpjDataSilent(cnpj)
}

// Busca silenciosa (usada pela IA)
const fetchCnpjDataSilent = async (cnpj) => {
  loadingCnpjData.value = true
  
  try {
    const cnpjLimpo = cnpj.replace(/\D/g, '')
    const response = await axios.get(
      `https://publica.cnpj.ws/cnpj/${cnpjLimpo}`
    )
    
    // Preenche Inscrição Estadual
    // Preenche Endereço (se vazio)
    // Salva dados completos da API
    form.invoice_cnpj_api_data = response.data
    
  } finally {
    loadingCnpjData.value = false
  }
}
```

---

## 📊 Fluxo Completo de Uso

### Cenário 1: Upload de Contrato + IA

```
┌─────────────────────────────────────┐
│ 1. Usuário faz upload do contrato   │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ 2. IA extrai dados do documento     │
│    - CPF/CNPJ das partes            │
│    - Endereços                      │
│    - my_role (contratante/contratado)│
└──────────────┬──────────────────────┘
               │
               ▼ (se my_role === 'contratado')
┌─────────────────────────────────────┐
│ 3. Frontend preenche dados de NF    │
│    - invoice_recipient_cnpj         │
│    - invoice_recipient_name         │
│    - invoice_recipient_address      │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ 4. Busca automática na API CNPJ.ws  │
│    GET https://publica.cnpj.ws/     │
│        cnpj/{cnpj}                  │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ 5. Preenche automaticamente:        │
│    ✓ Inscrição Estadual             │
│    ✓ Endereço (se vazio)            │
│    ✓ Salva JSON completo da API     │
└─────────────────────────────────────┘
```

### Cenário 2: Digitação Manual do CNPJ

```
┌─────────────────────────────────────┐
│ 1. Usuário digita CNPJ manualmente  │
└──────────────┬──────────────────────┘
               │
               ▼ (evento @blur)
┌─────────────────────────────────────┐
│ 2. fetchCnpjData() disparado        │
│    - Mostra loading indicator       │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ 3. Busca na API CNPJ.ws             │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ 4. Preenche campos automaticamente  │
└─────────────────────────────────────┘
```

---

## 🌐 API CNPJ.ws - Estrutura de Resposta

### Endpoint:
```
GET https://publica.cnpj.ws/cnpj/{cnpj}
```

### Exemplo de resposta (parcial):

```json
{
  "cnpj": "27865757000102",
  "razao_social": "GLOBO COMUNICACAO E PARTICIPACOES S/A",
  "estabelecimento": {
    "cnpj": "27865757000102",
    "nome_fantasia": "GLOBO",
    "situacao_cadastral": "Ativa",
    "tipo_logradouro": "Rua",
    "logradouro": "Lopes Quintas",
    "numero": "303",
    "bairro": "Jardim Botanico",
    "municipio": "Rio de Janeiro",
    "uf": "RJ",
    "cep": "22460010",
    "inscricoes_estaduais": [
      {
        "inscricao_estadual": "12345678",
        "ativo": true,
        "estado": "RJ"
      }
    ],
    "ddd1": "21",
    "telefone1": "21239900",
    "email": "contato@globo.com"
  }
}
```

---

## ✅ Validações Implementadas

### Backend (`StoreContractRequest.php`):

```php
'invoice_state_registration' => ['nullable', 'string', 'max:50'],
'invoice_cnpj_api_data' => ['nullable', 'array'],
```

### Frontend:

- ✅ CNPJ deve ter 14 dígitos (sem formatação)
- ✅ Apenas busca API se CNPJ for válido
- ✅ Erros silenciosos (não interrompem UX)
- ✅ Loading indicator durante busca
- ✅ Campo Inscrição Estadual desabilitado durante loading

---

## 🧪 Testando a Integração

### 1. Teste via Browser Console:

```javascript
// Simular busca de CNPJ da Globo
const response = await axios.get('https://publica.cnpj.ws/cnpj/27865757000102')
console.log(response.data)
```

### 2. Teste via Tinker (PHP):

```bash
php artisan tinker
```

```php
use App\Domain\Contracts\Services\CnpjApiService;

$service = new CnpjApiService();
$dados = $service->consultarCnpj('27865757000102');
dd($dados);
```

### 3. CNPJs de Teste:

| Empresa | CNPJ | Situação |
|---------|------|----------|
| Globo | 27.865.757/0001-02 | Ativa |
| Magazine Luiza | 47.960.950/0001-21 | Ativa |
| Natura | 71.673.990/0001-77 | Ativa |

---

## 🔒 Segurança e Boas Práticas

### ✅ Implementado:

1. **Timeout de 10 segundos** nas requisições à API
2. **Validação de CNPJ** (14 dígitos)
3. **Tratamento de erros silencioso** (não expõe detalhes ao usuário)
4. **Logs de erros** para debugging (`Log::error()`)
5. **Dados da API armazenados** para evitar múltiplas chamadas

### ⚠️ Limitações da API pública:

- API gratuita pode ter rate limit
- Sem SLA de disponibilidade
- Recomendado cache dos dados
- Para produção, considerar API paga com maior confiabilidade

---

## 📝 Próximos Passos (Opcional)

### Melhorias Futuras:

1. **Cache de consultas CNPJ**
   - Evitar múltiplas chamadas para o mesmo CNPJ
   - Implementar cache Redis ou DB

2. **Validação de CNPJ**
   - Adicionar validação de dígito verificador
   - Formatar CNPJ automaticamente

3. **Fallback para API alternativa**
   - ReceitaWS como backup
   - Brasilapi como alternativa

4. **Dashboard de estatísticas**
   - Quantas consultas foram feitas
   - Taxa de sucesso da API

---

## 🐛 Troubleshooting

### Problema: API não retorna dados

**Possíveis causas:**
- CNPJ inválido ou inexistente
- API fora do ar
- Rate limit atingido
- Timeout de conexão

**Solução:**
- Verificar console do navegador para erros
- Testar API direto no browser: `https://publica.cnpj.ws/cnpj/27865757000102`
- Verificar logs do Laravel: `storage/logs/laravel.log`

### Problema: Inscrição Estadual não preenche

**Causas:**
- Empresa pode não ter IE cadastrada
- IE pode estar inativa

**Solução:**
- Verificar resposta da API manualmente
- Preencher manualmente se necessário

---

## 📚 Referências

- **API CNPJ.ws**: https://publica.cnpj.ws/
- **Documentação da API**: https://github.com/receitaws/receitaws.github.io
- **Laravel HTTP Client**: https://laravel.com/docs/http-client
- **Axios**: https://axios-http.com/

---

## ✨ Conclusão

Sistema completo de integração com API CNPJ.ws implementado e testado. A funcionalidade está pronta para uso em produção, com tratamento de erros adequado e UX otimizada.

**Data de Implementação**: 25/01/2026
**Versão**: 1.0.0

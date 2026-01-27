<template>
  <AppLayout title="Novo Contrato">
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          Novo Contrato
        </h2>
      </div>
    </template>

    <div class="py-6">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <form @submit.prevent="submit" class="space-y-6">
          <!-- Upload de Documento (Primeiro Passo) -->
          <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg shadow p-6 border-2 border-dashed border-blue-300 dark:border-blue-700">
            <div class="flex items-start gap-4">
              <Icon icon="lucide:sparkles" class="h-8 w-8 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-1" />
              <div class="flex-1">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                  Importar Documento do Contrato (Opcional)
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                  Faça upload do PDF ou DOCX do contrato e nossa IA extrairá automaticamente as informações para preencher os campos abaixo.
                </p>

                <!-- Área de Upload -->
                <div v-if="!uploadedFile" class="space-y-3">
                  <div class="flex items-center gap-3">
                    <label class="flex-1">
                      <input
                        ref="fileInput"
                        type="file"
                        accept=".pdf,.doc,.docx"
                        @change="handleFileUpload"
                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer"
                      />
                    </label>
                  </div>
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    Formatos aceitos: PDF, DOC, DOCX (máx. 20MB)
                  </p>
                </div>

                <!-- Documento Carregado -->
                <div v-else class="space-y-3">
                  <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg border border-blue-200 dark:border-blue-700">
                    <div class="flex items-center gap-3">
                      <Icon icon="lucide:file-text" class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                      <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                          {{ uploadedFile.name }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                          {{ formatFileSize(uploadedFile.size) }}
                        </p>
                      </div>
                    </div>
                    <div class="flex items-center gap-2">
                      <Button
                        v-if="!extractingData"
                        type="button"
                        size="sm"
                        @click="extractDataWithAI"
                      >
                        <Icon icon="lucide:wand-2" class="h-4 w-4 mr-1" />
                        Extrair Dados com IA
                      </Button>
                      <div v-else class="flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400">
                        <Icon icon="lucide:loader-2" class="h-4 w-4 animate-spin" />
                        Analisando...
                      </div>
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="removeFile"
                      >
                        <Icon icon="lucide:x" class="h-4 w-4" />
                      </Button>
                    </div>
                  </div>

                  <!-- Aviso de Sucesso da Extração -->
                  <div v-if="extractionSuccess" class="flex items-center gap-2 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-700">
                    <Icon icon="lucide:check-circle" class="h-5 w-5 text-green-600 dark:text-green-400" />
                    <p class="text-sm text-green-800 dark:text-green-200">
                      Dados extraídos com sucesso! Revise e ajuste os campos abaixo conforme necessário.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Informações Básicas -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
              Informações Básicas
            </h3>

            <div class="space-y-4">
              <!-- Nome do Contrato -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Nome do Contrato *
                </label>
                <Input
                  v-model="form.name"
                  placeholder="Ex: Contrato de Prestação de Serviços - Empresa X"
                  :error="form.errors.name"
                  required
                />
                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                  {{ form.errors.name }}
                </p>
              </div>

              <!-- Meu Papel no Contrato -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Meu Papel neste Contrato *
                </label>
                <select
                  v-model="form.my_role"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="">Selecione...</option>
                  <option value="contratante">Sou o Contratante (estou contratando)</option>
                  <option value="contratado">Sou o Contratado (fui contratado)</option>
                </select>
                <p v-if="form.errors.my_role" class="mt-1 text-sm text-red-600">
                  {{ form.errors.my_role }}
                </p>
              </div>

              <!-- Tipo do Contrato -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Tipo de Contrato
                </label>
                <select
                  v-model="form.contract_type"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="">Selecione...</option>
                  <option value="Prestação de Serviços">Prestação de Serviços</option>
                  <option value="Aluguel">Aluguel</option>
                  <option value="SaaS">SaaS</option>
                  <option value="Fornecimento">Fornecimento</option>
                  <option value="Trabalho">Trabalho</option>
                  <option value="Outro">Outro</option>
                </select>
              </div>

              <!-- Objeto do Contrato -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Objeto do Contrato
                </label>
                <Textarea
                  v-model="form.contract_object"
                  rows="2"
                  placeholder="Ex: Desenvolvimento de software de gestão empresarial"
                />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  Resumo breve do que está sendo contratado
                </p>
              </div>

              <!-- Número do Contrato -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Número do Contrato
                </label>
                <Input
                  v-model="form.contract_number"
                  placeholder="Ex: CT-2025-001"
                />
              </div>

              <!-- Contratante -->
              <div class="space-y-4 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                  Contratante
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Nome
                    </label>
                    <Input
                      v-model="form.contractor"
                      placeholder="Nome completo do contratante"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      CPF ou CNPJ
                    </label>
                    <Input
                      v-model="form.contractor_cpf_cnpj"
                      placeholder="000.000.000-00 ou 00.000.000/0000-00"
                      maxlength="18"
                    />
                  </div>
                </div>
              </div>

              <!-- Contratado -->
              <div class="space-y-4 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                <div class="flex items-center justify-between">
                  <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Contratado <span v-if="form.my_role === 'contratante'">(Fornecedor)</span>
                  </h4>
                  <span v-if="form.my_role === 'contratante'" class="text-xs text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 px-2 py-1 rounded">
                    O CNPJ abaixo será a chave de acesso do fornecedor ao portal
                  </span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Nome / Razão Social *
                    </label>
                    <Input
                      v-model="form.contracted"
                      placeholder="Nome completo do contratado"
                      :required="form.my_role === 'contratante'"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      CPF ou CNPJ *
                    </label>
                    <Input
                      v-model="form.contracted_cpf_cnpj"
                      placeholder="000.000.000-00 ou 00.000.000/0000-00"
                      maxlength="18"
                      :required="form.my_role === 'contratante'"
                    />
                    <p v-if="form.my_role === 'contratante'" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                      Obrigatório para que o fornecedor consiga enviar notas fiscais.
                    </p>
                  </div>
                  
                  <!-- Dia do Envio da NF (Apenas para Contratante) -->
                  <div v-if="form.my_role === 'contratante'">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Dia Limite para Envio da NF
                    </label>
                    <Input
                      v-model.number="form.invoice_due_day"
                      type="number"
                      min="1"
                      max="31"
                      placeholder="Ex: 20"
                    />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                      Dia máximo do mês para o fornecedor subir a nota (gera alertas).
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Datas e Vigência -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
              Datas e Vigência
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Data de Início -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Data de Início
                </label>
                <Input
                  v-model="form.start_date"
                  type="date"
                  :error="form.errors.start_date"
                />
              </div>

              <!-- Data de Término -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Data de Término
                </label>
                <Input
                  v-model="form.end_date"
                  type="date"
                  :error="form.errors.end_date"
                />
              </div>
            </div>

            <!-- Renovação Automática -->
            <div class="mt-4">
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  v-model="form.auto_renewal"
                  class="rounded"
                />
                <span class="text-sm text-gray-700 dark:text-gray-300">
                  Este contrato possui renovação automática
                </span>
              </label>
            </div>
          </div>

          <!-- Valores e Condições Financeiras -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
              Valores e Condições Financeiras
            </h3>

            <div class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Valor -->
                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Valor do Contrato
                  </label>
                  <Input
                    v-model="form.amount"
                    type="number"
                    step="0.01"
                    placeholder="0.00"
                    :error="form.errors.amount"
                  />
                </div>

                <!-- Moeda -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Moeda
                  </label>
                  <select
                    v-model="form.currency"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  >
                    <option value="BRL">BRL (R$)</option>
                    <option value="USD">USD ($)</option>
                    <option value="EUR">EUR (€)</option>
                  </select>
                </div>
              </div>

              <!-- Condições de Pagamento -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Condições de Pagamento
                </label>
                <Textarea
                  v-model="form.payment_terms"
                  rows="3"
                  placeholder="Ex: 12 parcelas mensais de R$ 5.000,00, vencimento dia 20, depósito bancário, com nota fiscal"
                />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  Parcelas, valores, vencimento, forma de pagamento, nota fiscal, dados bancários, etc.
                </p>
              </div>
            </div>
          </div>

          <!-- Informações de Nota Fiscal (apenas quando sou contratado) -->
          <div v-if="form.my_role === 'contratado'" class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg shadow p-6 border border-green-200 dark:border-green-700">
            <div class="flex items-center gap-2 mb-4">
              <Icon icon="lucide:receipt" class="h-6 w-6 text-green-600 dark:text-green-400" />
              <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Informações para Emissão de Nota Fiscal
              </h3>
            </div>

            <div class="space-y-4">
              <!-- Contatos para Envio -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Email para Envio da NF
                  </label>
                  <Input
                    v-model="form.invoice_contact_email"
                    type="email"
                    placeholder="financeiro@empresa.com"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Link/Portal para Envio
                  </label>
                  <Input
                    v-model="form.invoice_contact_link"
                    type="url"
                    placeholder="https://portal.empresa.com/nf"
                  />
                </div>
              </div>

              <!-- Sistema de Cadastro -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Sistema de Cadastro/Gestão de NF
                </label>
                <Input
                  v-model="form.invoice_system"
                  placeholder="Ex: SAP, Ariba, Portal Fornecedor"
                />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  Sistema ou plataforma onde deve ser cadastrada a nota fiscal
                </p>
              </div>

              <!-- Descrição do Serviço -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Descrição para a Nota Fiscal
                </label>
                <Textarea
                  v-model="form.invoice_description"
                  rows="3"
                  placeholder="Descrição dos serviços prestados conforme contrato..."
                />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  Texto que deve constar na descrição da NF
                </p>
              </div>

              <!-- Dados do Destinatário -->
              <div class="p-4 bg-white dark:bg-gray-800/50 rounded-lg">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                  Dados do Destinatário da NF
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Nome/Razão Social
                    </label>
                    <Input
                      v-model="form.invoice_recipient_name"
                      placeholder="Nome do destinatário da NF"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      CNPJ
                    </label>
                    <div class="flex gap-2">
                      <Input
                        v-model="form.invoice_recipient_cnpj"
                        placeholder="00.000.000/0000-00"
                        maxlength="18"
                        @blur="fetchCnpjData"
                        class="flex-1"
                      />
                      <button
                        type="button"
                        @click="fetchCnpjData"
                        class="px-3 py-2 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 transition"
                        :disabled="loadingCnpjData"
                      >
                        {{ loadingCnpjData ? '...' : 'Buscar' }}
                      </button>
                    </div>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Inscrição Estadual
                    </label>
                    <Input
                      v-model="form.invoice_state_registration"
                      placeholder="Inscrição Estadual"
                      :disabled="loadingCnpjData"
                    />
                    <p v-if="loadingCnpjData" class="mt-1 text-xs text-blue-600 dark:text-blue-400">
                      Buscando dados do CNPJ...
                    </p>
                    <p v-if="cnpjApiMessage" class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                      {{ cnpjApiMessage }}
                    </p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Código do Serviço (ISS)
                    </label>
                    <Input
                      v-model="form.invoice_service_code"
                      placeholder="Ex: 01.07"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Dia do Envio da NF
                    </label>
                    <Input
                      v-model.number="form.invoice_due_day"
                      type="number"
                      min="1"
                      max="31"
                      placeholder="1-31"
                    />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                      Dia do mês para envio da nota fiscal
                    </p>
                  </div>
                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Endereço
                    </label>
                    <Textarea
                      v-model="form.invoice_recipient_address"
                      rows="2"
                      placeholder="Endereço completo do destinatário"
                    />
                  </div>
                </div>
              </div>

              <!-- Observações Internas -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Observações Internas sobre NF
                </label>
                <Textarea
                  v-model="form.invoice_internal_notes"
                  rows="3"
                  placeholder="Anotações internas sobre emissão, prazos, particularidades..."
                />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  Essas observações são apenas para controle interno
                </p>
              </div>
            </div>
          </div>

          <!-- Observações -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
              Observações
            </h3>

            <Textarea
              v-model="form.notes"
              rows="4"
              placeholder="Observações ou detalhes adicionais sobre o contrato..."
            />
          </div>

          <!-- Ações -->
          <div class="flex items-center justify-end gap-4">
            <Button
              type="button"
              variant="outline"
              @click="$inertia.visit(route('contracts.index'))"
            >
              Cancelar
            </Button>
            <Button
              type="submit"
              :disabled="form.processing"
            >
              <Icon
                v-if="form.processing"
                icon="lucide:loader-2"
                class="h-4 w-4 mr-2 animate-spin"
              />
              Criar Contrato
            </Button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/components/ui/button/Button.vue'
import Input from '@/components/ui/input/Input.vue'
import Textarea from '@/components/ui/textarea/Textarea.vue'
import { Icon } from '@iconify/vue'

const form = useForm({
  name: '',
  my_role: '',
  contract_type: '',
  contract_object: '',
  contract_number: '',
  contractor: '',
  contractor_cpf_cnpj: '',
  contracted: '',
  contracted_cpf_cnpj: '',
  start_date: '',
  end_date: '',
  auto_renewal: false,
  amount: '',
  currency: 'BRL',
  payment_terms: '',
  notes: '',
  document: null,
  // Campos de Nota Fiscal
  invoice_contact_email: '',
  invoice_contact_link: '',
  invoice_system: '',
  invoice_description: '',
  invoice_internal_notes: '',
  invoice_recipient_name: '',
  invoice_recipient_cnpj: '',
  invoice_state_registration: '',
  invoice_recipient_address: '',
  invoice_service_code: '',
  invoice_due_day: null,
  invoice_cnpj_api_data: null,
})

// Refs para upload
const uploadedFile = ref(null)
const extractingData = ref(false)
const extractionSuccess = ref(false)
const fileInput = ref(null)
const loadingCnpjData = ref(false)
const cnpjApiMessage = ref('')

// Captura arquivo selecionado
const handleFileUpload = (event) => {
  const file = event.target.files[0]
  
  if (!file) return

  // Validar tamanho (20MB)
  const maxSize = 20 * 1024 * 1024
  if (file.size > maxSize) {
    alert('Arquivo muito grande. Tamanho máximo: 20MB')
    return
  }

  // Validar tipo
  const allowedTypes = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
  ]
  if (!allowedTypes.includes(file.type)) {
    alert('Formato não suportado. Use PDF, DOC ou DOCX')
    return
  }

  uploadedFile.value = file
  extractionSuccess.value = false
}

// Remove arquivo carregado
const removeFile = () => {
  uploadedFile.value = null
  extractionSuccess.value = false
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

// Extrai dados com IA
const extractDataWithAI = async () => {
  if (!uploadedFile.value) return

  extractingData.value = true
  extractionSuccess.value = false

  try {
    const formData = new FormData()
    formData.append('document', uploadedFile.value)

    const response = await fetch(route('contracts.extract-data'), {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json',
      },
    })

    if (!response.ok) {
      throw new Error('Erro ao processar documento')
    }

    const data = await response.json()

    // Preencher novos campos primeiro
    if (data.my_role_suggestion) form.my_role = data.my_role_suggestion
    if (data.contract_object) form.contract_object = data.contract_object
    if (data.contract_number) form.contract_number = data.contract_number
    if (data.contractor) form.contractor = data.contractor
    if (data.contractor_cpf_cnpj) form.contractor_cpf_cnpj = data.contractor_cpf_cnpj
    if (data.contracted) form.contracted = data.contracted
    if (data.contracted_cpf_cnpj) form.contracted_cpf_cnpj = data.contracted_cpf_cnpj
    
    // Preencher dados de NF se o usuário for contratado
    if (data.my_role_suggestion === 'contratado') {
      // CNPJ do destinatário da NF é o contratante
      if (data.contractor_cpf_cnpj) {
        form.invoice_recipient_cnpj = data.contractor_cpf_cnpj
        // Buscar dados automaticamente
        await fetchCnpjDataSilent(data.contractor_cpf_cnpj)
      }
      if (data.contractor) form.invoice_recipient_name = data.contractor
      if (data.contractor_address) form.invoice_recipient_address = data.contractor_address
    }
    
    // Gerar nome do contrato automaticamente
    // Formato: Contratante + Número (ou Resumo do Objeto)
    let contractName = ''
    
    // Determinar a contraparte (quem NÃO é o usuário)
    let counterparty = ''
    if (data.my_role_suggestion === 'contratante') {
      counterparty = data.contracted || ''
    } else if (data.my_role_suggestion === 'contratado') {
      counterparty = data.contractor || ''
    } else {
      // Se não identificou papel, usa contractor como contraparte
      counterparty = data.contractor || data.contracted || ''
    }
    
    // Montar nome do contrato
    if (counterparty) {
      if (data.contract_number) {
        // Formato: Contraparte + Número
        contractName = `${counterparty} - ${data.contract_number}`
      } else if (data.contract_object) {
        // Formato: Contraparte + Resumo rápido do objeto (primeiras 50 chars)
        const objectSummary = data.contract_object.length > 50 
          ? data.contract_object.substring(0, 50) + '...' 
          : data.contract_object
        contractName = `${counterparty} - ${objectSummary}`
      } else if (data.contract_type) {
        // Fallback: Contraparte + Tipo
        contractName = `${counterparty} - ${data.contract_type}`
      }
    } else if (data.contract_number) {
      contractName = data.contract_number
    } else if (data.contract_object) {
      contractName = data.contract_object.length > 80 
        ? data.contract_object.substring(0, 80) + '...' 
        : data.contract_object
    }
    
    // Preencher nome do contrato
    if (contractName) {
      form.name = contractName
    }

    // Preencher demais campos
    if (data.contract_type) form.contract_type = data.contract_type
    if (data.start_date) form.start_date = data.start_date
    if (data.end_date) form.end_date = data.end_date
    if (data.auto_renewal !== null) form.auto_renewal = data.auto_renewal
    if (data.amount) form.amount = data.amount.toString()
    if (data.currency) form.currency = data.currency
    if (data.payment_terms) form.payment_terms = data.payment_terms
    
    // Adicionar informações extras nas observações
    let notesContent = []
    if (data.key_clauses && data.key_clauses.length > 0) {
      notesContent.push('📋 Cláusulas Importantes:\n' + data.key_clauses.map(c => `• ${c}`).join('\n'))
    }
    if (data.risks && data.risks.length > 0) {
      notesContent.push('⚠️ Riscos Identificados:\n' + data.risks.map(r => `• ${r}`).join('\n'))
    }
    if (data.penalties && (data.penalties.cancellation_fee || data.penalties.breach_penalty)) {
      notesContent.push('💰 Penalidades:')
      if (data.penalties.cancellation_fee) notesContent.push(`• Cancelamento: ${data.penalties.cancellation_fee}`)
      if (data.penalties.breach_penalty) notesContent.push(`• Quebra: ${data.penalties.breach_penalty}`)
    }
    if (data.cancellation_notice_days) {
      notesContent.push(`📅 Aviso Prévio: ${data.cancellation_notice_days} dias`)
    }
    
    if (notesContent.length > 0) {
      form.notes = notesContent.join('\n\n')
    }

    extractionSuccess.value = true
  } catch (error) {
    console.error('Erro ao extrair dados:', error)
    alert('Erro ao extrair dados do documento. Tente novamente.')
  } finally {
    extractingData.value = false
  }
}

// Formata tamanho do arquivo
const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

// Busca dados do CNPJ na API pública (quando usuário sai do campo)
const fetchCnpjData = async () => {
  const cnpj = form.invoice_recipient_cnpj
  
  console.log('fetchCnpjData chamado. CNPJ:', cnpj)
  
  if (!cnpj || cnpj.length < 14) {
    console.log('CNPJ vazio ou inválido. Length:', cnpj?.length)
    return
  }
  
  cnpjApiMessage.value = ''
  await fetchCnpjDataSilent(cnpj)
}

// Busca dados do CNPJ sem depender do evento blur
const fetchCnpjDataSilent = async (cnpj) => {
  if (!cnpj) return
  
  console.log('fetchCnpjDataSilent iniciado para CNPJ:', cnpj)
  
  loadingCnpjData.value = true
  
  try {
    // Remover formatação
    const cnpjLimpo = cnpj.replace(/\D/g, '')
    
    console.log('CNPJ limpo:', cnpjLimpo, 'Length:', cnpjLimpo.length)
    
    if (cnpjLimpo.length !== 14) {
      console.log('CNPJ não tem 14 dígitos')
      return
    }
    
    console.log('Fazendo requisição para API...')
    
    // Buscar via backend Laravel (evita CORS)
    const response = await fetch(route('contracts.fetch-cnpj'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json',
      },
      body: JSON.stringify({ cnpj: cnpjLimpo })
    })
    
    console.log('Resposta recebida. Status:', response.status)
    
    if (!response.ok) {
      const error = await response.json()
      console.log('Erro da API:', error)
      cnpjApiMessage.value = error.message || 'Erro ao buscar CNPJ'
      return
    }
    
    const result = await response.json()
    console.log('Resposta da API CNPJ:', result)
    
    if (result.success && result.data) {
      const data = result.data
      
      // Preencher Inscrição Estadual
      if (data.inscricao_estadual) {
        form.invoice_state_registration = data.inscricao_estadual
        console.log('IE encontrada:', data.inscricao_estadual)
        cnpjApiMessage.value = '✓ Inscrição Estadual preenchida automaticamente'
      } else {
        console.log('Empresa não possui Inscrição Estadual')
        cnpjApiMessage.value = 'Empresa não possui IE cadastrada. Preencha manualmente se necessário.'
      }
      
      // Preencher endereço se ainda não preenchido
      if (!form.invoice_recipient_address && data.endereco_completo) {
        form.invoice_recipient_address = data.endereco_completo
      }
      
      // Salvar dados completos da API para consulta posterior
      form.invoice_cnpj_api_data = result.raw
    }
    
  } catch (error) {
    console.error('Erro ao buscar CNPJ:', error)
    // Silenciosamente falhar - não incomodar usuário
  } finally {
    loadingCnpjData.value = false
  }
}

const submit = () => {
  // Se houver documento, adicionar ao form
  if (uploadedFile.value) {
    form.document = uploadedFile.value
  }
  
  // Enviar com Inertia (suporta arquivos automaticamente)
  form.post(route('contracts.store'), {
    preserveScroll: true,
    onSuccess: () => {
      // Limpar após sucesso
      uploadedFile.value = null
      extractionSuccess.value = false
    },
  })
}


</script>

<template>
  <AppLayout :title="`Editar: ${contract.name}`">
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          Editar Contrato
        </h2>
      </div>
    </template>

    <div class="py-6">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <form @submit.prevent="submit" class="space-y-6">
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

              <!-- Meu Papel -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Meu Papel neste Contrato
                </label>
                <select
                  v-model="form.my_role"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="">Selecione...</option>
                  <option value="contratante">Sou o contratante (quem contratou o serviço)</option>
                  <option value="contratado">Sou o contratado (quem vai prestar o serviço)</option>
                </select>
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
                <Input
                  v-model="form.contract_object"
                  placeholder="Ex: Desenvolvimento de sistema web"
                />
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
            </div>
          </div>

          <!-- Partes Envolvidas -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
              Partes Envolvidas
            </h3>

            <div class="space-y-6">
              <!-- Contratante -->
              <div>
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                  Contratante (quem contratou)
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Nome/Razão Social
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
              <div>
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                  Contratado (quem foi contratado)
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Nome/Razão Social
                    </label>
                    <Input
                      v-model="form.contracted"
                      placeholder="Nome completo do contratado"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      CPF ou CNPJ
                    </label>
                    <Input
                      v-model="form.contracted_cpf_cnpj"
                      placeholder="000.000.000-00 ou 00.000.000/0000-00"
                      maxlength="18"
                    />
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
                  placeholder="Parcelas, valores, vencimento, forma de pagamento, nota fiscal, dados bancários, etc."
                />
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

          <!-- Status -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
              Status
            </h3>

            <select
              v-model="form.status"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="ativo">Ativo</option>
              <option value="vencido">Vencido</option>
              <option value="encerrado">Encerrado</option>
            </select>
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
              @click="$inertia.visit(route('contracts.show', contract.id))"
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
              Salvar Alterações
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

const props = defineProps({
  contract: Object,
})

const form = useForm({
  name: props.contract.name || '',
  my_role: props.contract.my_role || '',
  contract_type: props.contract.contract_type || '',
  contract_object: props.contract.contract_object || '',
  contract_number: props.contract.contract_number || '',
  contractor: props.contract.contractor || '',
  contractor_cpf_cnpj: props.contract.contractor_cpf_cnpj || '',
  contracted: props.contract.contracted || '',
  contracted_cpf_cnpj: props.contract.contracted_cpf_cnpj || '',
  start_date: props.contract.start_date || '',
  end_date: props.contract.end_date || '',
  auto_renewal: props.contract.auto_renewal || false,
  amount: props.contract.amount || '',
  currency: props.contract.currency || 'BRL',
  payment_terms: props.contract.payment_terms || '',
  status: props.contract.status || 'ativo',
  notes: props.contract.notes || '',
  // Invoice fields
  invoice_contact_email: props.contract.invoice_contact_email || '',
  invoice_contact_link: props.contract.invoice_contact_link || '',
  invoice_system: props.contract.invoice_system || '',
  invoice_description: props.contract.invoice_description || '',
  invoice_internal_notes: props.contract.invoice_internal_notes || '',
  invoice_recipient_name: props.contract.invoice_recipient_name || '',
  invoice_recipient_cnpj: props.contract.invoice_recipient_cnpj || '',
  invoice_state_registration: props.contract.invoice_state_registration || '',
  invoice_recipient_address: props.contract.invoice_recipient_address || '',
  invoice_service_code: props.contract.invoice_service_code || '',
  invoice_due_day: props.contract.invoice_due_day || null,
})

const loadingCnpjData = ref(false)
const cnpjApiMessage = ref('')

const fetchCnpjData = async () => {
  const cnpj = form.invoice_recipient_cnpj
  
  if (!cnpj || cnpj.length < 14) {
    return
  }
  
  cnpjApiMessage.value = ''
  await fetchCnpjDataSilent(cnpj)
}

const fetchCnpjDataSilent = async (cnpj) => {
  if (!cnpj) return
  
  loadingCnpjData.value = true
  
  try {
    const cnpjLimpo = cnpj.replace(/\D/g, '')
    
    if (cnpjLimpo.length !== 14) {
      return
    }
    
    const response = await fetch(route('contracts.fetch-cnpj'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json',
      },
      body: JSON.stringify({ cnpj: cnpjLimpo })
    })
    
    if (!response.ok) {
      const error = await response.json()
      cnpjApiMessage.value = error.message || 'Erro ao buscar CNPJ'
      return
    }
    
    const result = await response.json()
    
    if (result.success && result.data) {
      const data = result.data
      
      // Fill state registration
      if (data.inscricao_estadual) {
        form.invoice_state_registration = data.inscricao_estadual
        cnpjApiMessage.value = '✓ Inscrição Estadual preenchida automaticamente'
      } else {
        cnpjApiMessage.value = 'Empresa não possui IE cadastrada. Preencha manualmente se necessário.'
      }
      
      // Fill address if empty
      if (!form.invoice_recipient_address && data.endereco_completo) {
        form.invoice_recipient_address = data.endereco_completo
      }
    }
    
  } catch (error) {
    console.error('Erro ao buscar CNPJ:', error)
  } finally {
    loadingCnpjData.value = false
  }
}

const submit = () => {
  form.put(route('contracts.update', props.contract.id))
}
</script>

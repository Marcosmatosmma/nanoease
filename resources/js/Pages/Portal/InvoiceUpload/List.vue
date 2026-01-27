<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Header -->
    <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <Icon icon="lucide:building-2" class="h-6 w-6 text-blue-600 dark:text-blue-400" />
          <span class="font-bold text-xl text-gray-900 dark:text-white">Portal do Fornecedor</span>
        </div>
        <div class="text-sm text-gray-500 dark:text-gray-400">
          CNPJ: <span class="font-medium text-gray-900 dark:text-gray-200">{{ cnpj }}</span>
        </div>
      </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      
      <!-- Mensagens Flash -->
      <div v-if="$page.props.flash.success" class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 flex items-center gap-3">
        <Icon icon="lucide:check-circle" class="h-5 w-5 text-green-600 dark:text-green-400" />
        <p class="text-sm text-green-700 dark:text-green-300">{{ $page.props.flash.success }}</p>
      </div>
      
      <div v-if="$page.props.flash.error" class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 flex items-center gap-3">
        <Icon icon="lucide:alert-circle" class="h-5 w-5 text-red-600 dark:text-red-400" />
        <p class="text-sm text-red-700 dark:text-red-300">{{ $page.props.flash.error }}</p>
      </div>

      <div class="mb-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Seus Contratos Ativos</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">
          Selecione um contrato para enviar sua Nota Fiscal (PDF ou XML)
        </p>
      </div>

      <!-- Lista de Contratos -->
      <div v-if="contracts.length === 0" class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg shadow">
        <Icon icon="lucide:folder-search" class="h-12 w-12 text-gray-400 mx-auto mb-3" />
        <p class="text-gray-500 dark:text-gray-400">Nenhum contrato ativo encontrado para este CNPJ.</p>
        <Link 
          :href="route('portal.supplier.index')" 
          class="text-blue-600 dark:text-blue-400 hover:underline text-sm mt-2 inline-block"
        >
          Voltar e tentar outro CNPJ
        </Link>
      </div>

      <div v-else class="space-y-4">
        <div 
          v-for="contract in contracts" 
          :key="contract.id"
          class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 transition-all hover:shadow-md"
        >
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-1">
                <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100">
                  {{ contract.name }}
                </h3>
                <span v-if="contract.contract_number" class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded text-gray-600 dark:text-gray-300">
                  {{ contract.contract_number }}
                </span>
              </div>
              
              <div class="flex flex-wrap gap-4 mt-2 text-sm text-gray-600 dark:text-gray-400">
                <div class="flex items-center gap-1.5" v-if="contract.invoice_due_day">
                  <Icon icon="lucide:calendar-clock" class="h-4 w-4 text-orange-500" />
                  <span>Dia de envio: <strong>{{ contract.invoice_due_day }}</strong> de cada mês</span>
                </div>
                <div class="flex items-center gap-1.5" v-if="contract.invoice_description">
                  <Icon icon="lucide:file-text" class="h-4 w-4 text-blue-500" />
                  <span class="truncate max-w-xs" :title="contract.invoice_description">
                    {{ contract.invoice_description }}
                  </span>
                </div>
              </div>
            </div>

            <div class="flex-shrink-0">
               <button 
                  @click="openUploadModal(contract)"
                  class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow-sm transition-colors"
               >
                  <Icon icon="lucide:upload-cloud" class="h-4 w-4" />
                  <span>Enviar Nota Fiscal</span>
               </button>
            </div>
          </div>
        </div>
      </div>
      
      <div class="mt-8 text-center">
        <Link 
          :href="route('portal.supplier.index')" 
          class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 flex items-center justify-center gap-1"
        >
          <Icon icon="lucide:arrow-left" class="h-4 w-4" />
          Sair / Trocar CNPJ
        </Link>
      </div>
    </main>

    <!-- Modal de Upload -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
      <div class="fixed inset-0 bg-gray-900/50 transition-opacity" @click="closeModal"></div>
      
      <div class="relative w-full max-w-lg bg-white dark:bg-gray-800 rounded-lg shadow-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gray-50 dark:bg-gray-700/50">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            Enviar Nota Fiscal
          </h3>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
            <Icon icon="lucide:x" class="h-5 w-5" />
          </button>
        </div>

        <form @submit.prevent="submitInvoice" class="p-6 space-y-4">
          <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-md mb-4 text-sm text-blue-700 dark:text-blue-300">
            <p class="font-medium">Contrato: {{ selectedContract?.name }}</p>
          </div>

          <!-- Número da NF -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Número da Nota Fiscal *
            </label>
            <Input
              v-model="form.invoice_number"
              placeholder="Ex: 12345"
              required
            />
          </div>

          <!-- Valor e Data -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Valor Total (R$) *
              </label>
              <Input
                v-model="form.amount"
                type="number"
                step="0.01"
                placeholder="0.00"
                required
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Data de Emissão *
              </label>
              <Input
                v-model="form.invoice_date"
                type="date"
                required
              />
            </div>
          </div>
          
           <!-- Vencimento -->
           <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Data de Vencimento
              </label>
              <Input
                v-model="form.due_date"
                type="date"
              />
              <p class="text-xs text-gray-500 mt-1">
                 Se deixar em branco, será considerado à vista.
              </p>
            </div>

          <!-- Arquivo -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Arquivo da Nota (PDF ou XML) *
            </label>
            <input
              type="file"
              ref="fileInput"
              accept=".pdf,.xml"
              class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer"
              @change="handleFileChange"
              required
            />
            <p v-if="form.errors.file" class="mt-1 text-sm text-red-600">
              {{ form.errors.file }}
            </p>
            <p class="mt-1 text-xs text-gray-500">
               Formatos: PDF, XML. Máx: 10MB.
            </p>
          </div>
          
          <!-- Descrição Opcional -->
          <div>
             <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
               Observações (Opcional)
             </label>
             <textarea 
                v-model="form.description"
                rows="2"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm"
                placeholder="Detalhes adicionais..."
             ></textarea>
          </div>

          <div class="flex justify-end pt-4 gap-3">
            <Button
              type="button"
              variant="outline"
              @click="closeModal"
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
                class="animate-spin h-4 w-4 mr-2" 
              />
              Enviar Nota Fiscal
            </Button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import Input from '@/components/ui/input/Input.vue'
import Button from '@/components/ui/button/Button.vue'

const props = defineProps({
  contracts: Array,
  cnpj: String,
})

const showModal = ref(false)
const selectedContract = ref(null)
const fileInput = ref(null)

const form = useForm({
  contract_id: null,
  cnpj: props.cnpj,
  invoice_number: '',
  amount: '',
  invoice_date: new Date().toISOString().split('T')[0],
  due_date: '',
  description: '',
  file: null,
})

const openUploadModal = (contract) => {
  selectedContract.value = contract
  form.contract_id = contract.id
  form.cnpj = props.cnpj
  form.invoice_number = ''
  form.amount = ''
  form.description = ''
  form.file = null
  
  // Reset file input manually
  if (fileInput.value) fileInput.value.value = ''
  
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  selectedContract.value = null
  form.reset()
}

const handleFileChange = (event) => {
  const file = event.target.files[0]
  if (file) {
     form.file = file
  }
}

const submitInvoice = () => {
  if (!form.file) {
    alert('Selecione um arquivo.')
    return
  }

  form.post(route('portal.supplier.contracts.invoice.store', form.contract_id), {
    preserveScroll: true,
    onSuccess: () => {
      closeModal()
      alert('Nota Fiscal enviada com sucesso!')
    },
    onError: (errors) => {
      if (errors.file) alert(errors.file)
      else console.error(errors)
    }
  })
}
</script>

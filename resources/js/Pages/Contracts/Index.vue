<template>
  <AppLayout title="Contratos">
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          Contratos
        </h2>
        <Button @click="$inertia.visit(route('contracts.create'))">
          <Icon icon="lucide:plus" class="h-4 w-4 mr-2" />
          Novo Contrato
        </Button>
      </div>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Filtros -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Busca -->
            <div class="md:col-span-2">
              <div class="relative">
                <Icon icon="lucide:search" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                <input
                  v-model="filters.search"
                  type="text"
                  placeholder="Buscar por nome ou tipo..."
                  @input="debouncedFilter"
                  class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
            </div>

            <!-- Status -->
            <select
              v-model="filters.status"
              @change="applyFilters"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="todos">Todos os status</option>
              <option value="ativo">Ativo</option>
              <option value="vencido">Vencido</option>
              <option value="encerrado">Encerrado</option>
            </select>

            <!-- Tipo -->
            <input
              v-model="filters.type"
              type="text"
              placeholder="Filtrar por tipo..."
              @input="debouncedFilter"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
        </div>

        <!-- Lista de Contratos -->
        <div v-if="contracts.length === 0" class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center">
          <Icon icon="lucide:file-text" class="h-16 w-16 text-gray-400 mx-auto mb-4" />
          <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
            Nenhum contrato encontrado
          </h3>
          <p class="text-gray-500 dark:text-gray-400 mb-4">
            Comece criando seu primeiro contrato
          </p>
          <Button @click="$inertia.visit(route('contracts.create'))">
            <Icon icon="lucide:plus" class="h-4 w-4 mr-2" />
            Criar Contrato
          </Button>
        </div>

        <div v-else class="space-y-4">
          <div
            v-for="contract in contracts"
            :key="contract.id"
            class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow cursor-pointer"
            @click="$inertia.visit(route('contracts.show', contract.id))"
          >
            <div class="p-6">
              <div class="flex items-start justify-between">
                <!-- Informações principais -->
                <div class="flex-1">
                  <div class="flex items-center gap-3 mb-2">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                      {{ contract.name }}
                    </h3>

                    <!-- Badge de Status -->
                    <span
                      class="px-2 py-1 text-xs font-medium rounded-full"
                      :class="getStatusClass(contract)"
                    >
                      {{ getStatusLabel(contract) }}
                    </span>
                  </div>

                  <!-- Detalhes -->
                  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm text-gray-600 dark:text-gray-400">
                    <div v-if="contract.contract_type">
                      <span class="font-medium">Tipo:</span>
                      {{ contract.contract_type }}
                    </div>

                    <div v-if="contract.end_date">
                      <span class="font-medium">Vencimento:</span>
                      {{ formatDate(contract.end_date) }}
                    </div>

                    <div v-if="contract.amount">
                      <span class="font-medium">Valor:</span>
                      {{ formatCurrency(contract.amount, contract.currency) }}
                    </div>

                    <div v-if="contract.auto_renewal !== null">
                      <span class="font-medium">Renovação:</span>
                      {{ contract.auto_renewal ? 'Automática' : 'Manual' }}
                    </div>
                  </div>

                  <!-- Meta informações -->
                  <div class="mt-3 flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                    <span>
                      <Icon icon="lucide:user" class="h-3 w-3 inline mr-1" />
                      {{ contract.created_by }}
                    </span>
                    <span>
                      <Icon icon="lucide:calendar" class="h-3 w-3 inline mr-1" />
                      {{ contract.created_at }}
                    </span>
                    <span v-if="contract.documents_count > 0">
                      <Icon icon="lucide:paperclip" class="h-3 w-3 inline mr-1" />
                      {{ contract.documents_count }} documento(s)
                    </span>
                  </div>
                </div>

                <!-- Ações -->
                <div class="flex gap-2 ml-4" @click.stop>
                  <Button
                    variant="ghost"
                    size="sm"
                    @click="$inertia.visit(route('contracts.edit', contract.id))"
                  >
                    <Icon icon="lucide:edit" class="h-4 w-4" />
                  </Button>
                  <Button
                    variant="ghost"
                    size="sm"
                    @click="confirmDelete(contract)"
                  >
                    <Icon icon="lucide:trash-2" class="h-4 w-4 text-red-600" />
                  </Button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/components/ui/button/Button.vue'
import { Icon } from '@iconify/vue'

const props = defineProps({
  contracts: Array,
  filters: Object,
})

const filters = ref({
  search: props.filters?.search || '',
  status: props.filters?.status || 'todos',
  type: props.filters?.type || '',
})

let debounceTimeout = null

const debouncedFilter = () => {
  clearTimeout(debounceTimeout)
  debounceTimeout = setTimeout(() => {
    applyFilters()
  }, 500)
}

const applyFilters = () => {
  router.get(route('contracts.index'), filters.value, {
    preserveState: true,
    preserveScroll: true,
  })
}

const getStatusClass = (contract) => {
  if (contract.is_expired || contract.status === 'vencido') {
    return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
  }
  if (contract.expires_today) {
    return 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200'
  }
  if (contract.expires_soon) {
    return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
  }
  if (contract.status === 'encerrado') {
    return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
  }
  return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
}

const getStatusLabel = (contract) => {
  if (contract.is_expired || contract.status === 'vencido') {
    return 'Vencido'
  }
  if (contract.expires_today) {
    return 'Vence Hoje'
  }
  if (contract.expires_soon) {
    return 'Vence em Breve'
  }
  if (contract.status === 'encerrado') {
    return 'Encerrado'
  }
  return 'Ativo'
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('pt-BR')
}

const formatCurrency = (amount, currency = 'BRL') => {
  if (!amount) return '-'
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: currency,
  }).format(amount)
}

const confirmDelete = (contract) => {
  if (confirm(`Tem certeza que deseja excluir o contrato "${contract.name}"?\n\nEsta ação não pode ser desfeita.`)) {
    router.delete(route('contracts.destroy', contract.id))
  }
}
</script>

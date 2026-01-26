<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/components/ui/button/Button.vue'
import Badge from '@/components/ui/badge/Badge.vue'

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

const getStatusVariant = (contract) => {
  if (contract.is_expired || contract.status === 'vencido') {
    return 'destructive'
  }
  if (contract.expires_today) {
    return 'destructive'
  }
  if (contract.expires_soon && contract.days_until_expiration !== null) {
    const days = contract.days_until_expiration
    if (days <= 7) {
      return 'default' // Laranja para menos de 7 dias
    } else if (days <= 15) {
      return 'secondary' // Amarelo para menos de 15 dias
    } else if (days <= 30) {
      return 'outline' // Cinza para menos de 30 dias
    }
  }
  if (contract.status === 'encerrado') {
    return 'outline'
  }
  return 'success'
}

const getStatusLabel = (contract) => {
  if (contract.is_expired || contract.status === 'vencido') {
    return 'Vencido'
  }
  if (contract.expires_today) {
    return 'Vence Hoje'
  }
  if (contract.expires_soon && contract.days_until_expiration !== null) {
    const days = contract.days_until_expiration
    if (days <= 0) {
      return 'Vence Hoje'
    } else if (days === 1) {
      return 'Vence em 1 dia'
    } else if (days <= 30) {
      return `Vence em ${days} dias`
    }
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

const confirmDelete = (e, contract) => {
  e.preventDefault()
  e.stopPropagation()
  if (confirm(`Tem certeza que deseja excluir o contrato "${contract.name}"?\n\nEsta ação não pode ser desfeita.`)) {
    router.delete(route('contracts.destroy', contract.id))
  }
}
</script>

<template>
  <AppLayout title="Contratos">
    <div class="space-y-6">
      <!-- Header -->
      <header class="space-y-2">
        <p class="text-sm text-muted-foreground">Dashboard</p>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 class="text-3xl font-semibold">Contratos</h1>
            <p class="text-muted-foreground">
              Gerencie contratos, alertas de vencimento e documentos em um só lugar.
            </p>
          </div>
          <Button @click="router.visit(route('contracts.create'))">
            Novo contrato
          </Button>
        </div>
      </header>

      <!-- Filtros -->
      <div class="rounded-lg border border-muted-foreground/20 p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <!-- Busca -->
          <div class="md:col-span-2">
            <div class="relative">
              <Icon icon="lucide:search" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <input
                v-model="filters.search"
                type="text"
                placeholder="Buscar por nome ou tipo..."
                @input="debouncedFilter"
                class="w-full pl-10 pr-4 py-2 border border-input rounded-md bg-background text-foreground focus:ring-2 focus:ring-ring focus:border-transparent"
              />
            </div>
          </div>

          <!-- Status -->
          <select
            v-model="filters.status"
            @change="applyFilters"
            class="w-full px-4 py-2 border border-input rounded-md bg-background text-foreground focus:ring-2 focus:ring-ring focus:border-transparent"
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
            class="w-full px-4 py-2 border border-input rounded-md bg-background text-foreground focus:ring-2 focus:ring-ring focus:border-transparent"
          />
        </div>
      </div>

      <!-- Lista de Contratos -->
      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold">Seus contratos</h2>
          <p class="text-sm text-muted-foreground">{{ contracts.length }} contrato(s)</p>
        </div>

        <!-- Estado vazio -->
        <div v-if="contracts.length === 0" class="rounded-lg border border-dashed p-8 text-center">
          <Icon icon="lucide:folder-open" class="mx-auto h-12 w-12 text-muted-foreground/50 mb-3" />
          <p class="text-muted-foreground mb-2">Nenhum contrato encontrado</p>
          <p class="text-sm text-muted-foreground/75">Comece clicando em "Novo contrato"</p>
        </div>

        <!-- Tabela de contratos -->
        <div v-else class="rounded-lg border border-muted-foreground/20 bg-card overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full table-fixed">
              <!-- Header -->
              <thead class="bg-muted/50">
                <tr class="border-b border-muted-foreground/20">
                  <th class="w-[35%] px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                    Contrato
                  </th>
                  <th class="w-[15%] px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider hidden md:table-cell">
                    Tipo
                  </th>
                  <th class="w-[15%] px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider hidden lg:table-cell">
                    Vencimento
                  </th>
                  <th class="w-[15%] px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider hidden xl:table-cell">
                    Valor
                  </th>
                  <th class="w-[10%] px-4 py-3 text-center text-xs font-medium text-muted-foreground uppercase tracking-wider">
                    Status
                  </th>
                  <th class="w-[10%] px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">
                    Ações
                  </th>
                </tr>
              </thead>

              <!-- Body -->
              <tbody class="divide-y divide-muted-foreground/10">
                <tr
                  v-for="contract in contracts"
                  :key="contract.id"
                  @click="router.visit(route('contracts.show', contract.id))"
                  class="hover:bg-muted/50 transition-colors cursor-pointer"
                >
                  <!-- Contrato -->
                  <td class="px-4 py-3">
                    <div class="flex items-start gap-3 min-w-0">
                      <Icon icon="lucide:file-text" class="h-5 w-5 text-primary flex-shrink-0 mt-0.5" />
                      <div class="min-w-0 flex-1">
                        <p class="font-medium text-foreground truncate" :title="contract.name">
                          {{ contract.name }}
                        </p>
                        <div class="flex flex-wrap items-center gap-2 mt-1 md:hidden">
                          <span class="text-xs text-muted-foreground truncate">{{ contract.contract_type }}</span>
                          <span v-if="contract.end_date" class="text-xs text-muted-foreground">
                            • {{ formatDate(contract.end_date) }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </td>

                  <!-- Tipo (hidden on mobile) -->
                  <td class="px-4 py-3 hidden md:table-cell">
                    <span class="text-sm text-muted-foreground truncate block" :title="contract.contract_type">
                      {{ contract.contract_type }}
                    </span>
                  </td>

                  <!-- Vencimento (hidden on mobile/tablet) -->
                  <td class="px-4 py-3 hidden lg:table-cell">
                    <div v-if="contract.end_date" class="flex items-center gap-2">
                      <Icon icon="lucide:calendar" class="h-4 w-4 text-muted-foreground flex-shrink-0" />
                      <span class="text-sm text-foreground truncate">{{ formatDate(contract.end_date) }}</span>
                    </div>
                    <span v-else class="text-sm text-muted-foreground">-</span>
                  </td>

                  <!-- Valor (hidden on mobile/tablet/small desktop) -->
                  <td class="px-4 py-3 hidden xl:table-cell">
                    <div v-if="contract.amount" class="flex items-center gap-2">
                      <Icon icon="lucide:dollar-sign" class="h-4 w-4 text-muted-foreground flex-shrink-0" />
                      <span class="text-sm font-medium text-foreground truncate">
                        {{ formatCurrency(contract.amount, contract.currency) }}
                      </span>
                    </div>
                    <span v-else class="text-sm text-muted-foreground">-</span>
                  </td>

                  <!-- Status -->
                  <td class="px-4 py-3 text-center">
                    <Badge :variant="getStatusVariant(contract)" class="inline-flex whitespace-nowrap">
                      {{ getStatusLabel(contract) }}
                    </Badge>
                  </td>

                  <!-- Ações -->
                  <td class="px-4 py-3 text-right" @click.stop>
                    <div class="flex items-center justify-end gap-1">
                      <button
                        @click.stop="router.visit(route('contracts.edit', contract.id))"
                        class="p-2 rounded-md hover:bg-accent transition-colors flex-shrink-0"
                        title="Editar"
                      >
                        <Icon icon="lucide:edit" class="h-4 w-4 text-muted-foreground" />
                      </button>
                      <button
                        @click.stop="confirmDelete($event, contract)"
                        class="p-2 rounded-md hover:bg-accent transition-colors flex-shrink-0"
                        title="Excluir"
                      >
                        <Icon icon="lucide:trash-2" class="h-4 w-4 text-red-600" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

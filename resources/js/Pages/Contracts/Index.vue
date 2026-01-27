<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/components/ui/button/Button.vue'
import Badge from '@/components/ui/badge/Badge.vue'
import Input from '@/components/ui/input/Input.vue'
import Label from '@/components/ui/label/Label.vue'
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
  SheetFooter,
} from '@/components/ui/sheet'

const props = defineProps({
  contracts: Array,
  filters: Object,
})

const filters = ref({
  search: props.filters?.search || '',
  status: props.filters?.status || 'todos',
  role: props.filters?.role || 'todos',
  type: props.filters?.type || '',
  start_date: props.filters?.start_date || '',
  end_date: props.filters?.end_date || '',
  min_amount: props.filters?.min_amount || '',
  max_amount: props.filters?.max_amount || '',
})

let debounceTimeout = null

const debouncedFilter = () => {
  clearTimeout(debounceTimeout)
  debounceTimeout = setTimeout(() => {
    // Para filtros via input texto, ainda mantemos debounce
    // Mas agora aplicamos todos os filtros juntos
  }, 500)
}

const applyFilters = () => {
  router.get(route('contracts.index'), filters.value, {
    preserveState: true,
    preserveScroll: true,
  })
}

const clearFilters = () => {
  filters.value = {
    search: '',
    status: 'todos',
    role: 'todos',
    type: '',
    start_date: '',
    end_date: '',
    min_amount: '',
    max_amount: '',
  }
  applyFilters()
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
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 class="text-3xl font-semibold">Contratos</h1>
        
          </div>
          <div class="flex gap-2">
             <Sheet>
              <SheetTrigger asChild>
                <Button variant="outline">
                  <Icon icon="lucide:filter" class="mr-2 h-4 w-4" />
                  Filtros
                </Button>
              </SheetTrigger>
              <SheetContent class="sm:max-w-lg">
                <SheetHeader>
                  <SheetTitle>Filtros Avançados</SheetTitle>
                  <SheetDescription>
                    Refine sua busca por contratos utilizando os filtros abaixo.
                  </SheetDescription>
                </SheetHeader>
                <div class="py-6 px-6 space-y-6">
                  <!-- Termo de Busca -->
                  <div class="space-y-2">
                    <Label>Buscar</Label>
                    <Input
                      v-model="filters.search"
                      placeholder="Nome do contrato ou tipo..."
                    />
                  </div>

                  <!-- Papel -->
                  <div class="space-y-2">
                    <Label>Seu Papel</Label>
                    <select
                      v-model="filters.role"
                      class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                      <option value="todos">Todos</option>
                      <option value="contratante">Contratante</option>
                      <option value="contratado">Contratado</option>
                    </select>
                  </div>

                  <!-- Status -->
                  <div class="space-y-2">
                    <Label>Status</Label>
                    <select
                      v-model="filters.status"
                      class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                      <option value="todos">Todos</option>
                      <option value="ativo">Ativo</option>
                      <option value="vencido">Vencido</option>
                      <option value="encerrado">Encerrado</option>
                    </select>
                  </div>

                  <!-- Tipo de Prestação -->
                   <div class="space-y-2">
                    <Label>Tipo de Prestação</Label>
                    <select
                      v-model="filters.type"
                      class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                      <option value="">Todos</option>
                      <option value="Prestação de Serviços">Prestação de Serviços</option>
                      <option value="Aluguel">Aluguel</option>
                      <option value="SaaS">SaaS</option>
                      <option value="Fornecimento">Fornecimento</option>
                      <option value="Trabalho">Trabalho</option>
                      <option value="Outro">Outro</option>
                    </select>
                  </div>

                  <!-- Vencimento -->
                  <div class="space-y-2">
                    <Label>Data de Vencimento</Label>
                    <div class="grid grid-cols-2 gap-2">
                      <div class="space-y-1">
                        <span class="text-xs text-muted-foreground">De</span>
                        <Input type="date" v-model="filters.start_date" />
                      </div>
                      <div class="space-y-1">
                        <span class="text-xs text-muted-foreground">Até</span>
                        <Input type="date" v-model="filters.end_date" />
                      </div>
                    </div>
                  </div>

                  <!-- Valor -->
                  <div class="space-y-2">
                    <Label>Valor (R$)</Label>
                    <div class="grid grid-cols-2 gap-2">
                      <div class="space-y-1">
                        <span class="text-xs text-muted-foreground">Mínimo</span>
                        <Input type="number" step="0.01" v-model="filters.min_amount" placeholder="0,00" />
                      </div>
                      <div class="space-y-1">
                        <span class="text-xs text-muted-foreground">Máximo</span>
                        <Input type="number" step="0.01" v-model="filters.max_amount" placeholder="0,00" />
                      </div>
                    </div>
                  </div>
                </div>
                <SheetFooter>
                  <div class="flex flex-col gap-2 w-full px-6">
                    <Button @click="applyFilters">Aplicar Filtros</Button>
                    <Button variant="outline" @click="clearFilters">Limpar Filtros</Button>
                  </div>
                </SheetFooter>
              </SheetContent>
            </Sheet>

            <Button @click="router.visit(route('contracts.create'))">
              Novo contrato
            </Button>
          </div>
        </div>
      </header>

      <!-- Lista de Contratos -->
      <div class="space-y-4">
        <div class="flex items-center justify-between">

          <p class="text-sm text-muted-foreground">{{ contracts.length }} contrato(s)</p>
        </div>

        <!-- Estado vazio -->
        <div v-if="contracts.length === 0" class="rounded-lg border border-dashed p-8 text-center">
          <Icon icon="lucide:folder-open" class="mx-auto h-12 w-12 text-muted-foreground/50 mb-3" />
          <p class="text-muted-foreground mb-2">Nenhum contrato encontrado</p>
          <p class="text-sm text-muted-foreground/75">Tente ajustar os filtros ou crie um novo contrato</p>
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
                    Papel
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
                    <div class="min-w-0">
                      <p class="text-sm text-muted-foreground truncate" :title="contract.name">
                        {{ contract.name }}
                      </p>
                        <div class="flex flex-wrap items-center gap-2 mt-1 md:hidden">
                          <Badge 
                            :variant="contract.my_role === 'contratante' ? 'outline' : 'secondary'"
                            class="uppercase text-[10px] mr-1"
                          >
                            {{ contract.my_role === 'contratante' ? 'Contratante' : 'Contratado' }}
                          </Badge>
                          <span class="text-xs text-muted-foreground truncate">{{ contract.contract_type }}</span>
                          <span v-if="contract.end_date" class="text-xs text-muted-foreground">
                            • {{ formatDate(contract.end_date) }}
                          </span>
                        </div>
                      </div>
                  </td>

                  <!-- Papel -->
                  <td class="px-4 py-3 hidden md:table-cell">
                    <Badge 
                      :variant="contract.my_role === 'contratante' ? 'outline' : 'secondary'"
                      class="uppercase text-[10px]"
                    >
                      {{ contract.my_role === 'contratante' ? 'Contratante' : 'Contratado' }}
                    </Badge>
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

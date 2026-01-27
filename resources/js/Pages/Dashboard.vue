<script setup>
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatsCard from '@/Components/StatsCard.vue'

const props = defineProps({
  stats: Object,
})

const formatCurrency = (value) => {
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  }).format(value)
}

const mainStats = computed(() => [
  {
    value: props.stats.total_active,
    description: 'Contratos Ativos',
    icon: 'lucide:file-text',
  },
  {
    value: props.stats.expiring_soon,
    description: 'Vencendo em 30 dias',
    icon: 'lucide:alert-triangle',
  },
  {
    value: formatCurrency(props.stats.by_role.contratante.value),
    description: 'Total a Pagar',
    icon: 'lucide:trending-down',
  },
  {
    value: formatCurrency(props.stats.by_role.contratado.value),
    description: 'Total a Receber',
    icon: 'lucide:trending-up',
  },
])
</script>

<template>
  <AppLayout title="Dashboard">
    <div class="space-y-6">
      
      <!-- Cabeçalho -->
      <div>
        <h2 class="text-2xl font-bold tracking-tight">Dashboard</h2>
        <p class="text-muted-foreground">Visão geral dos seus contratos e desempenho.</p>
      </div>

      <!-- Cards Principais -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <StatsCard
          v-for="(stat, index) in mainStats"
          :key="index"
          :value="stat.value"
          :description="stat.description"
          :icon="stat.icon"
        />
      </div>

    </div>
  </AppLayout>
</template>

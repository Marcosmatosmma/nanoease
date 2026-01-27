<script setup>
import { Icon } from '@iconify/vue'
import AppLayout from '@/layouts/AppLayout.vue'
import Badge from '@/components/ui/badge/Badge.vue'
import Button from '@/components/ui/button/Button.vue'
import Card from '@/components/ui/card/Card.vue'
import CardContent from '@/components/ui/card/CardContent.vue'
import CardDescription from '@/components/ui/card/CardDescription.vue'
import CardHeader from '@/components/ui/card/CardHeader.vue'
import CardTitle from '@/components/ui/card/CardTitle.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  connectedEmail: {
    type: String,
    default: null,
  },
  hasGmail: {
    type: Boolean,
    default: false,
  },
  automations: {
    type: Array,
    default: () => [],
  },
})

const actionLabel = (type) => {
  const map = {
    organizar: 'Organizar e classificar',
    encaminhar: 'Encaminhar e-mail',
    responder: 'Responder automaticamente',
    tarefa: 'Criar tarefa',
    envio_massa: 'Envio em massa',
  }
  return map[type] || 'Ação definida'
}

const statusVariant = (status) => {
  const map = {
    draft: 'secondary',
    active: 'success',
    paused: 'outline',
    processing: 'default',
    completed: 'success',
    scheduled: 'outline',
    failed: 'destructive',
  }
  return map[status] || 'outline'
}

const statusLabel = (status) => {
  const map = {
    draft: 'Rascunho',
    active: 'Ativa',
    paused: 'Pausada',
    processing: 'Processando',
    completed: 'Concluído',
    scheduled: 'Agendado',
    failed: 'Falhou',
  }
  return map[status] || 'Rascunho'
}

const nextStatus = (status) => {
  if (status === 'active') return 'paused'
  return 'active'
}

const nextStatusLabel = (status) => {
  return status === 'active' ? 'Pausar' : 'Ativar'
}
</script>

<template>
  <AppLayout title="Automações">
    <div class="space-y-6">
      <header class="space-y-2">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 class="text-3xl font-semibold">Automações</h1>
           
          </div>
          <Button :as="Link" :href="route('automations.new')" :disabled="!hasGmail">
            {{ hasGmail ? 'Nova automação' : 'Conecte o Gmail' }}
          </Button>
        </div>
      </header>

      <div class="space-y-3">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold">Suas automações</h2>
          <p class="text-sm text-muted-foreground">{{ automations.length }} automação(ões)</p>
        </div>

        <div v-if="automations.length === 0" class="rounded-lg border border-dashed p-6 text-center text-muted-foreground">
          Nenhuma automação criada ainda. Comece clicando em "Criar automação".
        </div>

        <div v-else class="grid gap-3 md:grid-cols-2">
          <Link
            v-for="item in automations"
            :key="item.id"
            :href="item.type === 'mass_email_send' 
              ? route('automations.email-mass-send.edit', { massEmailSend: item.id })
              : route('automations.email-received.edit', { automation: item.id })"
            class="block rounded-lg border border-muted-foreground/20 transition hover:border-primary"
          >
            <CardContent class="flex flex-col gap-3 p-4">
              <div class="flex items-start justify-between gap-3">
                <div class="space-y-1 flex-1">
                  <div class="flex items-center gap-2">
                    <Icon 
                      :icon="item.type === 'mass_email_send' ? 'lucide:mail-plus' : 'lucide:sparkles'" 
                      class="h-4 w-4 text-primary" 
                    />
                    <p class="font-semibold">
                      {{ item.event?.title || (item.type === 'mass_email_send' ? 'Envio em massa' : 'E-mail recebido') }}
                    </p>
                  </div>
                  <p class="text-sm text-muted-foreground truncate">
                    {{ item.rule }}
                  </p>
                  
                  <!-- Info específica de envio em massa -->
                  <div v-if="item.type === 'mass_email_send' && item.stats" class="flex items-center gap-2 text-xs text-muted-foreground">
                    <span>{{ item.stats.sent }}/{{ item.stats.total }} enviados</span>
                    <span v-if="item.stats.failed > 0" class="text-red-600">
                      · {{ item.stats.failed }} falhas
                    </span>
                  </div>
                  
                  <!-- Info de automação normal -->
                  <p v-else class="text-sm text-muted-foreground">
                    Ação: {{ actionLabel(item.action_type) }}
                  </p>
                </div>
                <Badge :variant="statusVariant(item.status)">
                  {{ statusLabel(item.status) }}
                </Badge>
              </div>
            </CardContent>
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

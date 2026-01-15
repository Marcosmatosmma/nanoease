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
  }
  return map[type] || 'Ação definida'
}

const statusVariant = (status) => {
  const map = {
    draft: 'secondary',
    active: 'success',
    paused: 'outline',
  }
  return map[status] || 'outline'
}

const statusLabel = (status) => {
  const map = {
    draft: 'Rascunho',
    active: 'Ativa',
    paused: 'Pausada',
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
        <p class="text-sm text-muted-foreground">Dashboard</p>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 class="text-3xl font-semibold">Automações</h1>
            <p class="text-muted-foreground">
              Evento → Interpretação → Ação. Comece pela leitura de e-mails já conectados.
            </p>
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
            :href="route('automations.email-received.edit', { automation: item.id })"
            class="block rounded-lg border border-muted-foreground/20 transition hover:border-primary"
          >
            <CardContent class="flex flex-col gap-3 p-4">
              <div class="flex items-start justify-between gap-3">
                <div class="space-y-1">
                  <div class="flex items-center gap-2">
                    <Icon icon="lucide:sparkles" class="h-4 w-4 text-primary" />
                    <p class="font-semibold">E-mail recebido</p>
                  </div>
                  <p class="text-sm text-muted-foreground truncate">
                    Regra: {{ item.rule }}
                  </p>
                  <p class="text-sm text-muted-foreground">
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

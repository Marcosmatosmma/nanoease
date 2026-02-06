<script setup>
import { Icon } from '@iconify/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Button from '@/Components/ui/button/Button.vue'
import Card from '@/Components/ui/card/Card.vue'
import CardContent from '@/Components/ui/card/CardContent.vue'
import CardDescription from '@/Components/ui/card/CardDescription.vue'
import CardHeader from '@/Components/ui/card/CardHeader.vue'
import CardTitle from '@/Components/ui/card/CardTitle.vue'
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  connectedEmail: {
    type: String,
    default: null,
  },
  hasGmail: {
    type: Boolean,
    default: false,
  },
  events: {
    type: Array,
    default: () => [],
  },
})

const eventRoute = (eventKey) => {
  const routeMap = {
    email_received: 'automations.email-received',
    email_mass_send: 'automations.email-mass-send',
    http_request: 'automations.http-request',
  }
  return routeMap[eventKey] || 'automations.index'
}

const groupedEvents = computed(() => {
  const groups = {}
  props.events.forEach((event) => {
    const category = event.category || 'Geral'
    if (!groups[category]) {
      groups[category] = []
    }
    groups[category].push(event)
  })
  return groups
})
</script>

<template>
  <AppLayout title="Selecionar evento">
    <div class="space-y-6">
      <header class="space-y-2">
        <div class="flex items-center gap-3">
          <Button :as="Link" :href="route('automations.index')" variant="outline" size="sm">
            <Icon icon="lucide:arrow-left" class="h-4 w-4" />
            Voltar
          </Button>
          <div class="space-y-1">
            <p class="text-sm text-muted-foreground">Criar automação</p>
            <h1 class="text-3xl font-semibold">Escolha o evento</h1>
          </div>
        </div>
        <p class="text-muted-foreground">
          Selecione qual evento você deseja automatizar. Cada evento tem suas próprias condições e ações disponíveis.
        </p>
      </header>

      <Card>
        <CardHeader class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
          <div class="space-y-1">
            <CardTitle>Conta conectada</CardTitle>
            <CardDescription>
              {{ hasGmail && connectedEmail ? connectedEmail : 'Conecte o Gmail para continuar.' }}
            </CardDescription>
          </div>
          <Badge :variant="hasGmail ? 'success' : 'outline'" class="flex items-center gap-1">
            <Icon :icon="hasGmail ? 'lucide:check-circle' : 'lucide:plug'" class="h-4 w-4" />
            {{ hasGmail ? 'Gmail conectado' : 'Aguardando conexão' }}
          </Badge>
        </CardHeader>
      </Card>

      <div v-for="(eventList, category) in groupedEvents" :key="category" class="space-y-3">
        <div>
          <h2 class="text-lg font-semibold">{{ category }}</h2>
          <p class="text-sm text-muted-foreground">
            {{ eventList.length }} evento(s) disponível(is)
          </p>
        </div>

        <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
          <Link
            v-for="event in eventList"
            :key="event.id"
            :href="route(eventRoute(event.key))"
            class="block rounded-lg border border-muted-foreground/20 transition hover:border-primary hover:shadow-md"
            :class="{ 'opacity-50 cursor-not-allowed pointer-events-none': !hasGmail }"
          >
            <Card>
              <CardContent class="flex flex-col gap-3 p-4">
                <div class="flex items-start gap-3">
                  <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <Icon :icon="event.icon" class="h-6 w-6" />
                  </span>
                  <div class="flex-1 space-y-1">
                    <p class="font-semibold">{{ event.title }}</p>
                    <p class="text-sm text-muted-foreground">
                      {{ event.description }}
                    </p>
                  </div>
                </div>
              </CardContent>
            </Card>
          </Link>
        </div>
      </div>

      <div v-if="events.length === 0" class="rounded-lg border border-dashed p-8 text-center">
        <Icon icon="lucide:inbox" class="mx-auto h-12 w-12 text-muted-foreground/50" />
        <p class="mt-3 text-muted-foreground">
          Nenhum evento disponível no momento.
        </p>
      </div>
    </div>
  </AppLayout>
</template>

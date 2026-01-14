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
  providers: {
    type: Array,
    required: true,
  },
  stats: {
    type: Object,
    required: true,
  },
})

const serviceIcons = {
  gmail: 'lucide:mail',
  google_agenda: 'lucide:calendar-range',
  banking: 'lucide:landmark',
  gdrive: 'lucide:folder',
}

function statusLabel(provider) {
  if (provider.comingSoon) return 'Em breve'
  return provider.status === 'connected' ? 'Conectado' : 'Desconectado'
}

function statusVariant(provider) {
  if (provider.comingSoon) return 'secondary'
  return provider.status === 'connected' ? 'success' : 'outline'
}

function connectRoute(provider) {
  if (provider.key === 'gmail') return route('integrations.gmail.redirect')
  return null
}

function disconnectRoute(provider) {
  if (provider.key === 'gmail') return route('integrations.gmail.disconnect')
  return null
}
</script>

<template>
  <AppLayout title="Integrações">
    <div class="space-y-6">
      <header class="space-y-2">
        <p class="text-sm text-muted-foreground">Dashboard</p>
        <h1 class="text-3xl font-semibold">Integrações</h1>
        <p class="text-muted-foreground">
          Quanto mais conexões, mais automações você pode criar.
        </p>
      </header>

      <Card>
        <CardHeader class="flex flex-row items-center justify-between">
          <div>
            <CardTitle>Serviços conectados</CardTitle>
            <CardDescription>{{ stats.connected }} de {{ stats.total }}</CardDescription>
          </div>
          <span
            class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 text-emerald-600"
          >
            <Icon icon="lucide:check-circle" class="h-5 w-5" />
          </span>
        </CardHeader>
      </Card>

      <div class="grid gap-4">
        <Card
          v-for="provider in providers"
          :key="provider.key"
          class="overflow-hidden"
        >
          <CardContent class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
              <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-muted/60">
                <Icon :icon="serviceIcons[provider.key] || 'lucide:plug'" class="h-5 w-5" />
              </span>
              <div>
                <div class="flex items-center gap-2">
                  <p class="font-medium">{{ provider.name }}</p>
                  <Badge :variant="statusVariant(provider)">
                    {{ statusLabel(provider) }}
                  </Badge>
                </div>
                <p class="text-sm text-muted-foreground">
                  {{ provider.description }}
                </p>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <template v-if="!provider.comingSoon">
                <div class="flex items-center gap-2">
                  <Button
                    v-if="provider.status !== 'connected'"
                    as="a"
                    :href="connectRoute(provider)"
                    variant="default"
                    size="sm"
                  >
                    Conectar
                  </Button>
                  <Button
                    v-else
                    :as="Link"
                    method="delete"
                    :href="disconnectRoute(provider)"
                    variant="secondary"
                    size="sm"
                  >
                    Desconectar
                  </Button>
                </div>
              </template>
              <Button v-else variant="outline" size="sm" disabled>
                Em breve
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>

      <Card class="border-amber-100 bg-amber-50">
        <CardContent class="flex flex-col gap-2 p-4">
          <div class="flex items-center gap-2 font-medium text-amber-900">
            <Icon icon="lucide:lock" class="h-4 w-4" />
            Suas informações estão seguras
          </div>
          <p class="text-sm text-amber-900/80">
            Nós usamos conexões oficiais e criptografadas. Você pode desconectar a qualquer momento e seus dados nunca são compartilhados.
          </p>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>

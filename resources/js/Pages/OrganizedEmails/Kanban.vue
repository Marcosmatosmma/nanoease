<script setup>
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Badge from '@/components/ui/badge/Badge.vue'
import Button from '@/components/ui/button/Button.vue'
import Card from '@/components/ui/card/Card.vue'
import CardContent from '@/components/ui/card/CardContent.vue'

const props = defineProps({
  emails: {
    type: Object,
    required: true,
  },
  automations: {
    type: Array,
    default: () => [],
  },
  selectedAutomationId: {
    type: Number,
    default: null,
  },
})

const viewMode = ref('kanban') // 'list' or 'kanban'

// Agrupar emails por label
const emailsByLabel = computed(() => {
  const groups = {}
  
  props.emails.data.forEach(email => {
    const label = email.gmail_label || 'Sem Label'
    if (!groups[label]) {
      groups[label] = []
    }
    groups[label].push(email)
  })
  
  return groups
})

const labels = computed(() => Object.keys(emailsByLabel.value).sort())

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
}

const openInGmail = (email) => {
  if (!email.gmail_id) return
  const url = `https://mail.google.com/mail/u/0/#all/${email.gmail_id}`
  window.open(url, '_blank')
}

const exportToCsv = () => {
  const params = props.selectedAutomationId 
    ? { automation_id: props.selectedAutomationId }
    : {}
  window.location.href = route('emails.organized.export', params)
}
</script>

<template>
  <AppLayout title="E-mails Organizados">
    <div class="space-y-6">
      <header class="space-y-2">
        <p class="text-sm text-muted-foreground">Dashboard</p>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 class="text-3xl font-semibold">E-mails Organizados</h1>
            <p class="text-muted-foreground">
              {{ emails.total }} e-mail(s) organizados
            </p>
          </div>
          <div class="flex gap-2">
            <!-- Toggle View Mode -->
            <div class="flex rounded-md border">
              <Button
                variant="ghost"
                size="sm"
                :class="viewMode === 'kanban' ? 'bg-accent' : ''"
                @click="viewMode = 'kanban'"
              >
                <Icon icon="lucide:kanban-square" class="h-4 w-4" />
              </Button>
              <Button
                variant="ghost"
                size="sm"
                :class="viewMode === 'list' ? 'bg-accent' : ''"
                @click="viewMode = 'list'"
              >
                <Icon icon="lucide:list" class="h-4 w-4" />
              </Button>
            </div>

            <Button :as="Link" :href="route('automations.index')" variant="outline">
              <Icon icon="lucide:settings" class="mr-2 h-4 w-4" />
              Automações
            </Button>
            
            <Button 
              v-if="emails.total > 0"
              variant="outline"
              @click="exportToCsv"
            >
              <Icon icon="lucide:download" class="mr-2 h-4 w-4" />
              CSV
            </Button>
          </div>
        </div>
      </header>

      <!-- Kanban View -->
      <div v-if="viewMode === 'kanban'" class="overflow-x-auto pb-4">
        <div class="flex gap-4 min-w-max">
          <div
            v-for="label in labels"
            :key="label"
            class="flex flex-col w-80 flex-shrink-0"
          >
            <!-- Column Header -->
            <Card class="mb-3">
              <CardContent class="p-3">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <Icon icon="lucide:tag" class="h-4 w-4 text-primary" />
                    <h3 class="font-semibold">{{ label }}</h3>
                  </div>
                  <Badge variant="secondary">{{ emailsByLabel[label].length }}</Badge>
                </div>
              </CardContent>
            </Card>

            <!-- Email Cards -->
            <div class="space-y-2 flex-1">
              <Card
                v-for="email in emailsByLabel[label]"
                :key="email.id"
                class="transition hover:shadow-md cursor-pointer"
                @click="openInGmail(email)"
              >
                <CardContent class="p-3 space-y-2">
                  <div class="flex items-start justify-between gap-2">
                    <p class="text-sm font-medium line-clamp-2">
                      {{ email.email_subject || 'Sem assunto' }}
                    </p>
                    <Icon icon="lucide:external-link" class="h-3 w-3 text-muted-foreground flex-shrink-0" />
                  </div>
                  
                  <div class="flex items-center gap-2 text-xs text-muted-foreground">
                    <Icon icon="lucide:user" class="h-3 w-3" />
                    <span class="truncate">{{ email.email_from || 'Desconhecido' }}</span>
                  </div>

                  <div class="flex items-center gap-2 text-xs text-muted-foreground">
                    <Icon icon="lucide:calendar" class="h-3 w-3" />
                    <span>{{ formatDate(email.email_date) }}</span>
                  </div>

                  <!-- Metadata from IA -->
                  <div v-if="email.metadata?.tipo" class="pt-2 border-t space-y-1">
                    <div class="flex items-center justify-between text-xs">
                      <span class="text-muted-foreground">Tipo:</span>
                      <Badge variant="outline" class="text-xs">{{ email.metadata.tipo }}</Badge>
                    </div>
                    <div v-if="email.metadata.valor" class="flex items-center justify-between text-xs">
                      <span class="text-muted-foreground">Valor:</span>
                      <span class="font-semibold">R$ {{ email.metadata.valor }}</span>
                    </div>
                    <div v-if="email.metadata.vencimento" class="flex items-center justify-between text-xs">
                      <span class="text-muted-foreground">Vencimento:</span>
                      <span>{{ new Date(email.metadata.vencimento).toLocaleDateString('pt-BR') }}</span>
                    </div>
                  </div>
                </CardContent>
              </Card>

              <div
                v-if="emailsByLabel[label].length === 0"
                class="text-center p-8 text-sm text-muted-foreground border-2 border-dashed rounded-lg"
              >
                Nenhum e-mail
              </div>
            </div>
          </div>
        </div>

        <div v-if="labels.length === 0" class="text-center py-12">
          <Icon icon="lucide:inbox" class="mx-auto h-12 w-12 text-muted-foreground opacity-40 mb-3" />
          <p class="font-medium">Nenhum e-mail organizado ainda</p>
          <p class="text-sm text-muted-foreground mt-1">
            Crie automações com ação "Organizar" para ver e-mails aqui
          </p>
        </div>
      </div>

      <!-- List View (original) -->
      <div v-else>
        <!-- Implementar visualização em lista aqui se necessário -->
        <p class="text-center py-12 text-muted-foreground">Visualização em lista (em breve)</p>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Icon } from '@iconify/vue'
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Badge from '@/components/ui/badge/Badge.vue'
import Button from '@/components/ui/button/Button.vue'
import Card from '@/components/ui/card/Card.vue'
import CardContent from '@/components/ui/card/CardContent.vue'
import Select from '@/components/ui/select/Select.vue'
import SelectContent from '@/components/ui/select/SelectContent.vue'
import SelectItem from '@/components/ui/select/SelectItem.vue'
import SelectTrigger from '@/components/ui/select/SelectTrigger.vue'
import SelectValue from '@/components/ui/select/SelectValue.vue'

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

const selectedFilter = computed({
  get: () => props.selectedAutomationId ? String(props.selectedAutomationId) : 'all',
  set: (value) => {
    if (value === 'all') {
      router.get(route('emails.organized'))
    } else {
      router.get(route('emails.organized', { automation_id: value }))
    }
  },
})

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
}

const openInGmail = (email) => {
  if (!email.gmail_id) return
  const url = `https://mail.google.com/mail/u/0/#all/${email.gmail_id}`
  window.open(url, '_blank')
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
              Visualize todos os e-mails organizados pelas suas automações
            </p>
          </div>
          <Button :as="Link" :href="route('automations.index')" variant="outline">
            <Icon icon="lucide:settings" class="mr-2 h-4 w-4" />
            Ver Automações
          </Button>
        </div>
      </header>

      <Card>
        <CardContent class="p-4">
          <div class="flex items-center gap-4">
            <div class="flex-1">
              <label class="mb-2 block text-sm font-medium">Filtrar por automação</label>
              <Select v-model="selectedFilter">
                <SelectTrigger>
                  <SelectValue placeholder="Todas as automações" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Todas as automações</SelectItem>
                  <SelectItem
                    v-for="automation in automations"
                    :key="automation.id"
                    :value="String(automation.id)"
                  >
                    {{ automation.gmail_label }} ({{ automation.rule_text }})
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
        </CardContent>
      </Card>

      <div class="space-y-3">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold">E-mails</h2>
          <p class="text-sm text-muted-foreground">
            {{ emails.total }} e-mail(s) encontrado(s)
          </p>
        </div>

        <div
          v-if="emails.data.length === 0"
          class="rounded-lg border border-dashed p-8 text-center text-muted-foreground"
        >
          <Icon icon="lucide:inbox" class="mx-auto mb-3 h-12 w-12 opacity-40" />
          <p class="font-medium">Nenhum e-mail organizado ainda</p>
          <p class="mt-1 text-sm">
            Crie automações com ação "Organizar" para ver e-mails aqui
          </p>
        </div>

        <div v-else class="space-y-2">
          <Card
            v-for="email in emails.data"
            :key="email.id"
            class="transition hover:border-primary"
          >
            <CardContent class="p-4">
              <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex-1 space-y-2">
                  <div class="flex items-center gap-2">
                    <Icon icon="lucide:mail" class="h-4 w-4 text-primary" />
                    <p class="font-semibold text-sm">{{ email.email_from || 'Remetente desconhecido' }}</p>
                    <Badge variant="outline" class="text-xs">
                      {{ email.gmail_label || 'Sem label' }}
                    </Badge>
                  </div>
                  
                  <p class="text-sm">{{ email.email_subject || 'Sem assunto' }}</p>
                  
                  <div class="flex items-center gap-4 text-xs text-muted-foreground">
                    <span>{{ formatDate(email.email_date) }}</span>
                    <span v-if="email.automation">
                      Automação: {{ email.automation.rule_text }}
                    </span>
                  </div>
                </div>

                <Button
                  v-if="email.gmail_id"
                  variant="outline"
                  size="sm"
                  @click="openInGmail(email)"
                >
                  <Icon icon="lucide:external-link" class="mr-2 h-3 w-3" />
                  Abrir no Gmail
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>

        <div v-if="emails.last_page > 1" class="flex items-center justify-center gap-2 pt-4">
          <Button
            v-for="page in emails.last_page"
            :key="page"
            :variant="page === emails.current_page ? 'default' : 'outline'"
            size="sm"
            :as="Link"
            :href="emails.links[page]?.url || '#'"
            :disabled="!emails.links[page]?.url"
          >
            {{ page }}
          </Button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

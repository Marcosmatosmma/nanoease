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

const viewMode = ref('kanban')

// Cores para cada coluna (ciclo de 8 cores)
const columnColors = [
  { bg: 'bg-blue-50', border: 'border-blue-200', header: 'bg-blue-100', text: 'text-blue-700' },
  { bg: 'bg-purple-50', border: 'border-purple-200', header: 'bg-purple-100', text: 'text-purple-700' },
  { bg: 'bg-green-50', border: 'border-green-200', header: 'bg-green-100', text: 'text-green-700' },
  { bg: 'bg-amber-50', border: 'border-amber-200', header: 'bg-amber-100', text: 'text-amber-700' },
  { bg: 'bg-pink-50', border: 'border-pink-200', header: 'bg-pink-100', text: 'text-pink-700' },
  { bg: 'bg-cyan-50', border: 'border-cyan-200', header: 'bg-cyan-100', text: 'text-cyan-700' },
  { bg: 'bg-orange-50', border: 'border-orange-200', header: 'bg-orange-100', text: 'text-orange-700' },
  { bg: 'bg-teal-50', border: 'border-teal-200', header: 'bg-teal-100', text: 'text-teal-700' },
]

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

const getColumnColor = (index) => {
  return columnColors[index % columnColors.length]
}

const getColumnTotal = (label) => {
  return emailsByLabel.value[label]
    .reduce((sum, email) => {
      const valor = email.metadata?.valor || 0
      return sum + parseFloat(valor)
    }, 0)
}

const formatCurrency = (value) => {
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  }).format(value)
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit',
    month: 'short',
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
    <div class="space-y-4">
      <header class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold">E-mails Organizados</h1>
          <p class="text-sm text-muted-foreground">
            {{ emails.total }} e-mail(s) • Visualização Kanban
          </p>
        </div>
        <div class="flex gap-2">
          <Button
            variant="ghost"
            size="sm"
            :as="Link"
            :href="route('automations.index')"
          >
            <Icon icon="lucide:settings" class="mr-2 h-4 w-4" />
            Automações
          </Button>
          
          <Button 
            v-if="emails.total > 0"
            variant="outline"
            size="sm"
            @click="exportToCsv"
          >
            <Icon icon="lucide:download" class="mr-2 h-4 w-4" />
            Exportar
          </Button>
        </div>
      </header>

      <!-- Kanban Board -->
      <div class="overflow-x-auto pb-4 -mx-6 px-6">
        <div class="flex gap-4 min-w-max">
          <div
            v-for="(label, index) in labels"
            :key="label"
            class="flex flex-col w-80 flex-shrink-0"
          >
            <!-- Column Header -->
            <div 
              class="rounded-t-lg p-3 border-b-2"
              :class="[getColumnColor(index).header, getColumnColor(index).border]"
            >
              <div class="flex items-center justify-between mb-2">
                <h3 class="font-semibold" :class="getColumnColor(index).text">
                  {{ label }}
                </h3>
                <Badge variant="secondary" class="text-xs">
                  {{ emailsByLabel[label].length }}
                </Badge>
              </div>
              <div 
                v-if="getColumnTotal(label) > 0"
                class="text-sm font-bold"
                :class="getColumnColor(index).text"
              >
                {{ formatCurrency(getColumnTotal(label)) }}
              </div>
            </div>

            <!-- Cards Container -->
            <div 
              class="flex-1 p-3 space-y-3 min-h-[400px] rounded-b-lg border-x border-b"
              :class="[getColumnColor(index).bg, getColumnColor(index).border]"
            >
              <div
                v-for="email in emailsByLabel[label]"
                :key="email.id"
                class="bg-white rounded-lg border shadow-sm hover:shadow-md transition-all cursor-pointer group"
                @click="openInGmail(email)"
              >
                <div class="p-3 space-y-2">
                  <!-- Header com ícone de abrir -->
                  <div class="flex items-start justify-between gap-2">
                    <p class="text-sm font-medium line-clamp-2 flex-1">
                      {{ email.email_subject || 'Sem assunto' }}
                    </p>
                    <Icon 
                      icon="lucide:external-link" 
                      class="h-4 w-4 text-muted-foreground opacity-0 group-hover:opacity-100 transition flex-shrink-0"
                    />
                  </div>

                  <!-- Remetente -->
                  <div class="flex items-center gap-2 text-xs text-muted-foreground">
                    <Icon icon="lucide:user" class="h-3 w-3 flex-shrink-0" />
                    <span class="truncate">{{ email.email_from?.split('<')[0]?.trim() || 'Desconhecido' }}</span>
                  </div>

                  <!-- Data -->
                  <div class="flex items-center gap-2 text-xs text-muted-foreground">
                    <Icon icon="lucide:clock" class="h-3 w-3 flex-shrink-0" />
                    <span>{{ formatDate(email.email_date) }}</span>
                  </div>

                  <!-- Metadados da IA -->
                  <div v-if="email.metadata?.tipo || email.metadata?.valor" class="pt-2 border-t space-y-1.5">
                    <div v-if="email.metadata.tipo" class="flex items-center gap-2">
                      <Badge variant="outline" class="text-xs capitalize">
                        {{ email.metadata.tipo }}
                      </Badge>
                    </div>
                    
                    <div v-if="email.metadata.valor" class="flex items-center justify-between">
                      <span class="text-xs text-muted-foreground">Valor:</span>
                      <span class="text-sm font-bold text-green-600">
                        {{ formatCurrency(email.metadata.valor) }}
                      </span>
                    </div>
                    
                    <div v-if="email.metadata.vencimento" class="flex items-center justify-between">
                      <span class="text-xs text-muted-foreground">Vencimento:</span>
                      <span class="text-xs font-medium">
                        {{ new Date(email.metadata.vencimento).toLocaleDateString('pt-BR') }}
                      </span>
                    </div>

                    <div v-if="email.metadata.empresa" class="flex items-center gap-2">
                      <Icon icon="lucide:building" class="h-3 w-3 text-muted-foreground" />
                      <span class="text-xs truncate">{{ email.metadata.empresa }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Empty state -->
              <div
                v-if="emailsByLabel[label].length === 0"
                class="text-center py-8 text-xs text-muted-foreground"
              >
                <Icon icon="lucide:inbox" class="mx-auto h-8 w-8 opacity-40 mb-2" />
                <p>Nenhum e-mail</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty state geral -->
        <div v-if="labels.length === 0" class="text-center py-20">
          <Icon icon="lucide:inbox" class="mx-auto h-16 w-16 text-muted-foreground opacity-20 mb-4" />
          <p class="text-lg font-medium text-muted-foreground">Nenhum e-mail organizado ainda</p>
          <p class="text-sm text-muted-foreground mt-2">
            Crie automações com ação "Organizar" para ver e-mails aqui
          </p>
          <Button :as="Link" :href="route('automations.index')" variant="default" class="mt-4">
            <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
            Criar Automação
          </Button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

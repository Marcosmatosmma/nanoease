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
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'

/**
 * Props do componente de visualização
 */
const props = defineProps({
  massEmailSend: {
    type: Object,
    required: true,
  },
  recipients: {
    type: Array,
    default: () => [],
  },
})

const deleting = ref(false)
const resending = ref(false)

/**
 * Apaga o envio em massa
 */
function deleteMassEmailSend() {
  if (!window.confirm('Deseja realmente apagar este envio em massa?')) return
  
  deleting.value = true
  router.delete(route('automations.mass-email-send.destroy', { massEmailSend: props.massEmailSend.id }), {
    onFinish: () => {
      deleting.value = false
    },
  })
}

/**
 * Reenvia emails que falharam
 */
function resendFailedEmails() {
  if (!window.confirm('Deseja reenviar os emails que falharam?')) return
  
  resending.value = true
  router.post(route('automations.mass-email-send.resend', { massEmailSend: props.massEmailSend.id }), {}, {
    onFinish: () => {
      resending.value = false
    },
  })
}

/**
 * Retorna variante do badge baseado no status
 */
const statusVariant = (status) => {
  const map = {
    pending: 'outline',
    sent: 'success',
    failed: 'destructive',
    ignored: 'secondary',
  }
  return map[status] || 'outline'
}

/**
 * Retorna label do status
 */
const statusLabel = (status) => {
  const map = {
    pending: 'Pendente',
    sent: 'Enviado',
    failed: 'Falhou',
    ignored: 'Ignorado',
  }
  return map[status] || status
}

/**
 * Retorna variante do status do envio
 */
const sendStatusVariant = (status) => {
  const map = {
    draft: 'secondary',
    processing: 'default',
    completed: 'success',
    scheduled: 'outline',
    failed: 'destructive',
  }
  return map[status] || 'outline'
}
</script>

<template>
  <AppLayout :title="`Envio: ${massEmailSend.name}`">
    <div class="space-y-6">
      <!-- Header -->
      <header class="space-y-2">
        <div class="flex items-center gap-3">
          <Button :as="Link" :href="route('automations.index')" variant="outline" size="sm">
            <Icon icon="lucide:arrow-left" class="h-4 w-4" />
            Voltar
          </Button>
        </div>
        <div class="flex items-start justify-between">
          <div>
            <h1 class="text-2xl font-bold">{{ massEmailSend.name }}</h1>
            <p class="text-muted-foreground">
              Relatório do envio em massa
            </p>
          </div>
          <Badge :variant="sendStatusVariant(massEmailSend.status)" class="text-base px-3 py-1">
            {{ massEmailSend.status === 'completed' ? 'Concluído' : massEmailSend.status === 'processing' ? 'Processando' : massEmailSend.status }}
          </Badge>
        </div>
      </header>

      <!-- Estatísticas -->
      <div class="grid gap-4 md:grid-cols-4">
        <Card>
          <CardContent class="pt-6">
            <div class="text-center">
              <div class="text-3xl font-bold">{{ massEmailSend.total_recipients }}</div>
              <div class="text-xs text-muted-foreground mt-1">Total</div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="pt-6">
            <div class="text-center">
              <div class="text-3xl font-bold text-green-600">{{ massEmailSend.sent_count }}</div>
              <div class="text-xs text-muted-foreground mt-1">Enviados</div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="pt-6">
            <div class="text-center">
              <div class="text-3xl font-bold text-red-600">{{ massEmailSend.failed_count }}</div>
              <div class="text-xs text-muted-foreground mt-1">Falhas</div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="pt-6">
            <div class="text-center">
              <div class="text-3xl font-bold text-amber-600">{{ massEmailSend.invalid_recipients }}</div>
              <div class="text-xs text-muted-foreground mt-1">Ignorados</div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Detalhes do envio -->
      <Card>
        <CardHeader>
          <CardTitle>Detalhes do Envio</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid gap-4 md:grid-cols-2">
            <div>
              <div class="text-sm font-medium text-muted-foreground mb-1">Assunto</div>
              <div class="font-medium">{{ massEmailSend.subject }}</div>
            </div>

            <div>
              <div class="text-sm font-medium text-muted-foreground mb-1">Tipo de envio</div>
              <div class="font-medium">
                {{ massEmailSend.send_type === 'now' ? 'Imediato' : 'Agendado' }}
                <span v-if="massEmailSend.scheduled_at" class="text-muted-foreground ml-2">
                  · {{ new Date(massEmailSend.scheduled_at).toLocaleString('pt-BR') }}
                </span>
              </div>
            </div>

            <div>
              <div class="text-sm font-medium text-muted-foreground mb-1">Iniciado em</div>
              <div class="font-medium">
                {{ massEmailSend.started_at ? new Date(massEmailSend.started_at).toLocaleString('pt-BR') : '-' }}
              </div>
            </div>

            <div>
              <div class="text-sm font-medium text-muted-foreground mb-1">Concluído em</div>
              <div class="font-medium">
                {{ massEmailSend.completed_at ? new Date(massEmailSend.completed_at).toLocaleString('pt-BR') : '-' }}
              </div>
            </div>
          </div>

          <div>
            <div class="text-sm font-medium text-muted-foreground mb-1">Corpo do e-mail</div>
            <div class="rounded-lg bg-muted p-4 text-sm whitespace-pre-wrap">{{ massEmailSend.body }}</div>
          </div>
        </CardContent>
      </Card>

      <!-- Lista de destinatários -->
      <Card v-if="recipients.length > 0">
        <CardHeader>
          <div class="flex items-center justify-between">
            <div>
              <CardTitle>Destinatários</CardTitle>
              <CardDescription>{{ recipients.length }} destinatário(s)</CardDescription>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <div class="space-y-2">
            <div 
              v-for="recipient in recipients" 
              :key="recipient.id"
              class="flex items-center justify-between rounded-lg border p-3"
            >
              <div class="flex-1 space-y-1">
                <div class="font-medium">{{ recipient.email }}</div>
                <div v-if="recipient.data" class="text-xs text-muted-foreground">
                  <span v-if="recipient.data.nome">{{ recipient.data.nome }}</span>
                  <span v-if="recipient.data.empresa" class="ml-2">· {{ recipient.data.empresa }}</span>
                </div>
                <div v-if="recipient.error_message || recipient.ignore_reason" class="text-xs text-red-600">
                  {{ recipient.error_message || recipient.ignore_reason }}
                </div>
                <div v-if="recipient.sent_at" class="text-xs text-muted-foreground">
                  Enviado em {{ new Date(recipient.sent_at).toLocaleString('pt-BR') }}
                </div>
              </div>
              <Badge :variant="statusVariant(recipient.status)">
                {{ statusLabel(recipient.status) }}
              </Badge>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Ações -->
      <Card>
        <CardContent class="pt-6">
          <div class="flex items-center justify-between">
            <p class="text-sm text-muted-foreground">
              Este envio não pode ser editado. Para fazer um novo envio, crie uma nova automação.
            </p>
            <div class="flex gap-3">
              <Button 
                v-if="massEmailSend.failed_count > 0"
                variant="outline" 
                :disabled="resending"
                @click="resendFailedEmails"
              >
                <Icon v-if="resending" icon="lucide:loader-2" class="mr-2 h-4 w-4 animate-spin" />
                <Icon v-else icon="lucide:refresh-cw" class="mr-2 h-4 w-4" />
                {{ resending ? 'Reenviando...' : 'Reenviar falhas' }}
              </Button>
              <Button 
                variant="destructive" 
                :disabled="deleting"
                @click="deleteMassEmailSend"
              >
                <Icon v-if="deleting" icon="lucide:loader-2" class="mr-2 h-4 w-4 animate-spin" />
                <Icon v-else icon="lucide:trash-2" class="mr-2 h-4 w-4" />
                {{ deleting ? 'Apagando...' : 'Apagar envio' }}
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>

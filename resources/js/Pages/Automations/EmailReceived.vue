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
import Textarea from '@/components/ui/textarea/Textarea.vue'
import Input from '@/components/ui/input/Input.vue'
import RichTextEditor from '@/components/RichTextEditor.vue'
import GmailLabelSelector from '@/components/GmailLabelSelector.vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

const props = defineProps({
  connectedEmail: {
    type: String,
    default: null,
  },
  hasGmail: {
    type: Boolean,
    default: false,
  },
  automation: {
    type: Object,
    default: null,
  },
  triggerTypes: {
    type: Array,
    default: () => [],
  },
  executions: {
    type: Array,
    default: () => [],
  },
})

const actions = [
  {
    key: 'organizar',
    title: 'Organizar e classificar',
    description: 'Mover para pasta e adicionar marcadores.',
    icon: 'lucide:folder'
  },
  {
    key: 'encaminhar',
    title: 'Encaminhar e-mail',
    description: 'Enviar para outra pessoa automaticamente.',
    icon: 'lucide:send'
  },
  {
    key: 'responder',
    title: 'Responder automaticamente',
    description: 'Disparar uma resposta pré-definida.',
    icon: 'lucide:message-circle'
  },
  {
    key: 'tarefa',
    title: 'Criar tarefa',
    description: 'Adicionar à sua lista de tarefas.',
    icon: 'lucide:check-square'
  },
]

const form = useForm({
  trigger_type_id: props.triggerTypes[0]?.id || null,
  rule: '',
  action_type: 'organizar',
  action_config: {},
  gmail_label: '',
})

const simulation = ref(null)
const simulating = ref(false)
const simulationError = ref(null)
const forwardToInput = ref('')
const replySubject = ref('Re: {subject}')
const replyBody = ref('')
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
const xsrfToken = document.cookie
  .split('; ')
  .find((row) => row.startsWith('XSRF-TOKEN='))
  ?.split('=')[1]
const isEditing = computed(() => !!props.automation?.id)
const currentStatus = ref(props.automation?.status || 'draft')
const deleting = ref(false)

if (props.automation) {
  form.trigger_type_id = props.automation.trigger_type_id || form.trigger_type_id
  form.rule = props.automation.rule_text || props.automation.rule || form.rule
  form.action_type = props.automation.action_type || form.action_type
  form.action_config = props.automation.action_config || {}
  form.gmail_label = props.automation.gmail_label || ''
  
  if (props.automation.action_type === 'encaminhar') {
    forwardToInput.value = (props.automation.action_config?.forward_to ?? []).join(', ')
  }
  
  if (props.automation.action_type === 'responder') {
    replySubject.value = props.automation.action_config?.reply_subject || 'Re: {subject}'
    replyBody.value = props.automation.action_config?.reply_body || ''
  }
}

syncActionConfig()

watch(
  () => form.action_type,
  (type) => {
    if (type !== 'encaminhar') {
      forwardToInput.value = ''
    }
    if (type !== 'responder') {
      replySubject.value = 'Re: {subject}'
      replyBody.value = ''
    }
    if (type !== 'organizar') {
      form.gmail_label = ''
    }
    syncActionConfig()
  },
)

watch(
  () => form.trigger_type_id,
  () => {
    form.errors.rule = ''
  },
)

watch(
  () => form.rule,
  () => {
    form.errors.rule = ''
  },
)

function translateError(message) {
  if (!message) return ''
  if (message.includes('must be at least')) return 'A regra precisa ter pelo menos 6 caracteres.'
  if (message.toLowerCase().includes('csrf')) return 'Sessão expirada. Recarregue a página e tente novamente.'
  if (message.toLowerCase().includes('forward to')) return 'Informe pelo menos um e-mail para encaminhar.'
  if (message.toLowerCase().includes('reply_body')) return 'Digite o corpo da resposta.'
  if (message.toLowerCase().includes('email')) return 'Informe e-mails válidos separados por vírgula.'
  return message
}

const forwardToError = computed(() => form.errors['action_config.forward_to'] || form.errors['action_config.forward_to.0'])
const replyBodyError = computed(() => form.errors['action_config.reply_body'])

const selectedTrigger = computed(() => props.triggerTypes.find((t) => t.id === form.trigger_type_id))

const needsAI = computed(() => selectedTrigger.value?.uses_ai || false)

const actionLabel = computed(() => {
  const map = {
    organizar: 'organizar e classificar',
    encaminhar: 'encaminhar',
    responder: 'responder automaticamente',
    tarefa: 'criar tarefa',
  }
  return map[form.action_type] || form.action_type
})

const simulationVisual = computed(() => {
  if (!simulation.value) {
    return {
      variant: 'neutral',
      bg: 'bg-white/70 border border-emerald-100 text-emerald-900',
      icon: 'lucide:info',
      title: 'Pré-visualização',
    }
  }

  const isExecute = simulation.value.decision === 'executar'
  return {
    variant: isExecute ? 'success' : 'danger',
    bg: isExecute
      ? 'bg-emerald-50 border border-emerald-200 text-emerald-900'
      : 'bg-rose-50 border border-rose-200 text-rose-900',
    icon: isExecute ? 'lucide:check-circle' : 'lucide:x-circle',
    title: isExecute ? 'Vai executar' : 'Será ignorada',
  }
})

function syncActionConfig() {
  if (form.action_type === 'encaminhar') {
    const emails = forwardToInput.value
      .split(',')
      .map((email) => email.trim())
      .filter(Boolean)

    form.action_config = { forward_to: emails }
    return
  }
  
  if (form.action_type === 'responder') {
    form.action_config = {
      reply_subject: replySubject.value,
      reply_body: replyBody.value,
    }
    return
  }

  form.action_config = {}
}

function submit() {
  if (!props.hasGmail) return
  
  // Validação frontend
  if (!form.rule || form.rule.trim() === '') {
    form.errors.rule = 'A condição não pode estar vazia.'
    return
  }
  
  // Validação específica por tipo de trigger
  if (selectedTrigger.value?.key === 'sender_exact') {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    if (!emailRegex.test(form.rule.trim())) {
      form.errors.rule = 'Informe um e-mail válido (ex: usuario@dominio.com)'
      return
    }
  }
  
  if (selectedTrigger.value?.key === 'sender_domain') {
    const domainRegex = /^@?([a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,})$/i
    const cleaned = form.rule.trim().toLowerCase()
    if (!domainRegex.test(cleaned)) {
      form.errors.rule = 'Informe um domínio válido (ex: @empresa.com ou empresa.com)'
      return
    }
  }
  
  syncActionConfig()
  const url = isEditing.value
    ? route('automations.email-received.store', { automation: props.automation.id })
    : route('automations.email-received.store')

  form.post(url, {
    preserveScroll: true,
    onSuccess: () => {
      if (props.automation?.id) {
        currentStatus.value = 'draft'
      }
    },
  })
}

async function simulate() {
  if (!props.hasGmail || simulating.value) return
  simulationError.value = null
  simulating.value = true
  try {
    syncActionConfig()
    const response = await fetch(route('automations.email-received.simulate'), {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken || '',
        'X-XSRF-TOKEN': xsrfToken ? decodeURIComponent(xsrfToken) : '',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({
        _token: csrfToken,
        rule: form.rule,
        action_type: form.action_type,
        action_config: form.action_config,
      }),
      credentials: 'same-origin',
    })

    if (!response.ok) {
      const error = await response.json()
      simulationError.value = error.message || 'Não foi possível simular.'
      simulation.value = null
      return
    }

    simulation.value = await response.json()
  } catch (error) {
    simulationError.value = 'Erro de rede ao simular.'
    simulation.value = null
  } finally {
    simulating.value = false
  }
}

function toggleStatus() {
  if (!props.automation?.id) return
  const next = currentStatus.value === 'active' ? 'paused' : 'active'
  router.patch(
    route('automations.status', { automation: props.automation.id }),
    { status: next },
    {
      preserveScroll: true,
      onSuccess: () => {
        currentStatus.value = next
      },
    },
  )
}

function deleteAutomation() {
  if (!props.automation?.id || deleting.value) return
  if (!window.confirm('Deseja realmente apagar esta automação?')) return
  deleting.value = true
  form.delete(route('automations.destroy', { automation: props.automation.id }), {
    onFinish: () => {
      deleting.value = false
    },
  })
}
</script>

<template>
  <AppLayout title="Nova automação - E-mail recebido">
    <div class="space-y-6">
      <header class="space-y-2">
        <div class="flex items-center gap-3">
          <Button :as="Link" :href="route('automations.index')" variant="outline" size="sm">
            <Icon icon="lucide:arrow-left" class="h-4 w-4" />
            Voltar
          </Button>
          <div class="space-y-1">
            <p class="text-sm text-muted-foreground">Passo a passo</p>
            <h1 class="text-3xl font-semibold">E-mail recebido</h1>
          </div>
        </div>
        <p class="text-muted-foreground">
          Evento → interpretamos a regra → decidimos a ação. Sem lógica técnica para você.
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

      <div class="grid gap-4 lg:grid-cols-[1.2fr_1fr]">
        <Card class="border-primary/10">
          <CardHeader>
            <div class="flex items-center gap-2">
              <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                <Icon icon="lucide:mail" class="h-4 w-4" />
              </span>
              <div>
                <CardTitle>1) Evento: novo e-mail recebido</CardTitle>
                <CardDescription>Capturamos cada mensagem que chegar na sua caixa.</CardDescription>
              </div>
            </div>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="space-y-2">
              <CardTitle class="text-base">2) Como identificar esse e-mail?</CardTitle>
              <CardDescription>Escolha o jeito mais fácil de reconhecer a mensagem.</CardDescription>
              <div class="grid gap-3">
                <button
                  v-for="trigger in props.triggerTypes"
                  :key="trigger.id"
                  type="button"
                  class="flex items-start gap-3 rounded-lg border p-3 text-left transition hover:border-primary"
                  :class="{
                    'border-primary bg-primary/5': form.trigger_type_id === trigger.id,
                    'opacity-50 cursor-not-allowed': !hasGmail,
                  }"
                  :disabled="!hasGmail"
                  @click="form.trigger_type_id = trigger.id"
                >
                  <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-muted">
                    <Icon :icon="trigger.icon" class="h-4 w-4" />
                  </span>
                  <div class="flex-1 space-y-1">
                    <p class="font-medium">{{ trigger.title }}</p>
                    <p class="text-sm text-muted-foreground">{{ trigger.description }}</p>
                    <p v-if="trigger.key === 'sender_exact'" class="text-xs text-emerald-700">
                      Ex: suporte@empresa.com
                    </p>
                    <p v-else-if="trigger.key === 'sender_domain'" class="text-xs text-emerald-700">
                      Ex: @empresa.com (todos os e-mails desse domínio)
                    </p>
                    <p v-else-if="trigger.key === 'subject_contains'" class="text-xs text-emerald-700">
                      Ex: "fatura", "boleto", "nota fiscal"
                    </p>
                    <p v-else-if="trigger.key === 'content_semantic'" class="text-xs text-emerald-700">
                      Ex: "e-mails sobre pagamentos em atraso"
                    </p>
                  </div>
                </button>
              </div>
            </div>

            <div class="space-y-2">
              <CardTitle class="text-base">3) Defina a condição</CardTitle>
              <CardDescription>{{ selectedTrigger.description }}</CardDescription>
              
              <Textarea
                v-model="form.rule"
                :rows="needsAI ? 3 : 2"
                :placeholder="selectedTrigger?.placeholder"
              />
              
              <p v-if="selectedTrigger?.key === 'sender_exact'" class="text-xs text-muted-foreground">
                💡 Exemplo: mitsloan@getsmarter.com ou suporte@empresa.com
              </p>
              <p v-else-if="selectedTrigger?.key === 'sender_domain'" class="text-xs text-muted-foreground">
                💡 Captura TODOS os e-mails de @adidas.com, @mit.edu, etc
              </p>
              <p v-else-if="selectedTrigger?.key === 'subject_contains'" class="text-xs text-muted-foreground">
                💡 Separe por vírgula para buscar múltiplas palavras
              </p>
              <p v-else-if="selectedTrigger?.key === 'content_semantic'" class="text-xs text-muted-foreground">
                💡 Descreva a intenção ou contexto que você quer identificar
              </p>
              <p v-if="form.errors.rule" class="mt-2 text-sm text-destructive">
                {{ translateError(form.errors.rule) }}
              </p>
            </div>

            <div class="space-y-2">
              <CardTitle class="text-base">4) Escolha a ação</CardTitle>
              <CardDescription>O sistema só executa ações definidas por você.</CardDescription>
              <div class="grid gap-3 md:grid-cols-2">
                <button
                  v-for="item in actions"
                  :key="item.key"
                  type="button"
                  class="flex items-start gap-3 rounded-lg border p-3 text-left transition hover:border-primary"
                  :class="{
                    'border-primary bg-primary/5': form.action_type === item.key,
                    'opacity-50 cursor-not-allowed': !hasGmail,
                  }"
                  :disabled="!hasGmail"
                  @click="form.action_type = item.key"
                >
                  <span class="flex h-9 w-9 items-center justify-center rounded-md bg-muted">
                    <Icon :icon="item.icon" class="h-4 w-4" />
                  </span>
                  <div class="space-y-1">
                    <p class="font-medium">{{ item.title }}</p>
                    <p class="text-sm text-muted-foreground">{{ item.description }}</p>
                  </div>
                </button>
              </div>
            </div>

            <div v-if="form.action_type === 'encaminhar'" class="space-y-2">
              <CardTitle class="text-base">5) Para onde encaminhar?</CardTitle>
              <CardDescription>Separe múltiplos e-mails por vírgula.</CardDescription>
              <Input
                v-model="forwardToInput"
                type="text"
                placeholder="ex.: equipe@empresa.com, financeiro@empresa.com"
                @blur="syncActionConfig"
              />
              <p v-if="forwardToError" class="text-sm text-destructive">
                {{ translateError(forwardToError) }}
              </p>
            </div>

            <div v-if="form.action_type === 'organizar'" class="space-y-2">
              <CardTitle class="text-base">5) Nome da label no Gmail</CardTitle>
              <CardDescription>
                Selecione uma label existente ou crie uma nova. Use "/" para criar hierarquia.
              </CardDescription>
              <GmailLabelSelector 
                v-model="form.gmail_label"
                :disabled="!hasGmail"
              />
            </div>

            <div v-if="form.action_type === 'responder'" class="space-y-4">
              <div class="space-y-2">
                <CardTitle class="text-base">5) Configure a resposta automática</CardTitle>
                <CardDescription>
                  Defina o assunto e corpo da resposta. Use variáveis como {from_name}, {subject}, etc.
                </CardDescription>
              </div>

              <div class="space-y-2">
                <label class="text-sm font-medium">Assunto da resposta</label>
                <Input
                  v-model="replySubject"
                  type="text"
                  placeholder="Re: {subject}"
                  @blur="syncActionConfig"
                />
                <p class="text-xs text-muted-foreground">
                  💡 Use {subject} para referenciar o assunto original
                </p>
              </div>

              <div class="space-y-2">
                <label class="text-sm font-medium">Corpo da resposta</label>
                <RichTextEditor
                  v-model="replyBody"
                  placeholder="Digite sua resposta... Use o botão IA para melhorar o texto!"
                  :disabled="!hasGmail"
                  @update:model-value="syncActionConfig"
                />
                <p v-if="replyBodyError" class="text-sm text-destructive">
                  {{ translateError(replyBodyError) }}
                </p>
                <p class="text-xs text-muted-foreground">
                  💡 Use variáveis: {from_name}, {from_email}, {subject}, {date}, {body_preview}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card :class="simulationVisual.bg">
          <CardContent class="space-y-3 p-4">
            <div class="flex items-center gap-2" :class="simulationVisual.variant === 'danger' ? 'text-rose-900' : 'text-emerald-900'">
              <Icon :icon="simulationVisual.icon" class="h-4 w-4" />
              <p class="font-semibold">{{ simulationVisual.title }}</p>
            </div>
            <p class="text-sm" :class="simulationVisual.variant === 'danger' ? 'text-rose-900/80' : 'text-emerald-900/80'">
              Quando chegar um e-mail novo, o sistema verifica se atende à condição e executa a ação escolhida.
              Você define a ação final - o sistema apenas identifica e executa.
            </p>
            <div class="rounded-lg p-3 text-sm" :class="simulationVisual.variant === 'danger' ? 'bg-white text-rose-900 border border-rose-200' : 'bg-white/70 text-emerald-900'">
              <div class="space-y-2">
                <div class="flex items-center gap-2">
                  <Icon icon="lucide:mail" class="h-4 w-4" />
                  <span class="font-semibold">Email recebido</span>
                </div>
                
                <div class="ml-6 space-y-1.5">
                  <div class="flex items-center gap-2 text-emerald-700">
                    <Icon icon="lucide:arrow-down" class="h-4 w-4" />
                    <span class="font-medium">Trigger: {{ selectedTrigger?.title }}</span>
                  </div>
                  
                  <div v-if="form.rule" class="ml-6 text-sm text-muted-foreground">
                    <span v-if="selectedTrigger?.key === 'sender_exact'">✉️ {{ form.rule }}</span>
                    <span v-else-if="selectedTrigger?.key === 'sender_domain'">🌐 *@{{ form.rule.replace('@', '') }}</span>
                    <span v-else-if="selectedTrigger?.key === 'subject_contains'">📝 "{{ form.rule }}"</span>
                    <span v-else-if="selectedTrigger?.key === 'content_semantic'">🧠 {{ form.rule.substring(0, 50) }}{{ form.rule.length > 50 ? '...' : '' }}</span>
                  </div>
                  
                  <div class="flex items-center gap-2 text-emerald-700">
                    <Icon icon="lucide:arrow-right" class="h-4 w-4" />
                    <span class="font-medium">Ação: {{ actionLabel }}</span>
                  </div>
                  
                  <div
                    v-if="form.action_type === 'encaminhar' && forwardToInput"
                    class="ml-6 text-sm text-emerald-700"
                  >
                    📨 {{ forwardToInput }}
                  </div>
                </div>
              </div>
              
              <div
                v-if="simulation"
                class="mt-3 space-y-2 rounded-md p-2"
                :class="simulationVisual.variant === 'danger'
                  ? 'border border-rose-200 bg-rose-50 text-rose-900'
                  : 'border border-emerald-100 bg-emerald-50 text-emerald-900'"
              >
                <div class="flex items-center gap-2">
                  <Icon :icon="simulationVisual.icon" class="h-4 w-4" />
                  <span>Validação: {{ simulation.decision }}</span>
                </div>
                <p class="text-sm" :class="simulationVisual.variant === 'danger' ? 'text-rose-800' : 'text-emerald-800'">
                  {{ simulation.summary }}
                </p>
                <p v-if="simulation.integration_email" class="text-xs" :class="simulationVisual.variant === 'danger' ? 'text-rose-700' : 'text-emerald-700'">
                  Conta: {{ simulation.integration_email }}
                </p>
              </div>
              <p v-if="simulationError" class="mt-2 text-sm text-destructive">
                {{ translateError(simulationError) }}
              </p>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="secondary" :disabled="!hasGmail || simulating" @click="simulate">
                {{ simulating ? 'Validando...' : 'Validar' }}
              </Button>
              <Button
                v-if="isEditing"
                variant="outline"
                :disabled="form.processing"
                @click="toggleStatus"
              >
                {{ currentStatus === 'active' ? 'Pausar' : 'Ativar' }}
              </Button>
              <Button
                v-if="isEditing"
                variant="destructive"
                :disabled="deleting || form.processing"
                @click="deleteAutomation"
              >
                {{ deleting ? 'Apagando...' : 'Apagar' }}
              </Button>
              <Button :disabled="!hasGmail || form.processing" @click="submit">
                {{ form.processing ? 'Salvando...' : (isEditing ? 'Salvar' : 'Salvar rascunho') }}
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>

      <Card v-if="isEditing && props.executions.length > 0">
        <CardHeader>
          <div class="flex items-center justify-between">
            <div>
              <CardTitle>Histórico de execuções</CardTitle>
              <CardDescription>Últimas 50 execuções desta automação</CardDescription>
            </div>
            <Badge variant="outline" class="flex items-center gap-1">
              <Icon icon="lucide:history" class="h-4 w-4" />
              {{ props.executions.length }}
            </Badge>
          </div>
        </CardHeader>
        <CardContent>
          <div class="space-y-2">
            <div
              v-for="execution in props.executions"
              :key="execution.id"
              class="flex items-start gap-3 rounded-lg border p-3 transition hover:bg-muted/50"
            >
              <Badge
                :variant="execution.status === 'success' ? 'success' : execution.status === 'failed' ? 'destructive' : execution.status === 'processing' ? 'default' : 'outline'"
                class="mt-1 shrink-0"
              >
                <Icon
                  :icon="execution.status === 'success' ? 'lucide:check-circle' : execution.status === 'failed' ? 'lucide:x-circle' : execution.status === 'processing' ? 'lucide:loader-2' : 'lucide:minus-circle'"
                  :class="{ 'animate-spin': execution.status === 'processing' }"
                  class="h-3 w-3"
                />
                {{ execution.status }}
              </Badge>
              
              <div class="flex-1 space-y-1">
                <div class="flex items-start justify-between gap-2">
                  <div class="flex-1 space-y-0.5">
                    <p class="font-medium text-sm">{{ execution.email_subject || '(sem assunto)' }}</p>
                    <p class="text-xs text-muted-foreground">De: {{ execution.email_from }}</p>
                  </div>
                  <span class="text-xs text-muted-foreground shrink-0">
                    {{ new Date(execution.created_at).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
                  </span>
                </div>
                
                <div v-if="execution.reasoning" class="rounded-md bg-muted/50 p-2 text-xs">
                  <p class="text-muted-foreground">{{ execution.reasoning }}</p>
                  <div v-if="execution.confidence !== null" class="mt-1 flex items-center gap-1.5">
                    <Icon icon="lucide:gauge" class="h-3 w-3" />
                    <span class="font-medium">{{ Math.round(execution.confidence * 100) }}%</span>
                  </div>
                </div>

                <div v-if="execution.action_result && execution.action_result.message" class="rounded-md bg-muted/50 p-2 text-xs">
                  <p class="text-muted-foreground">{{ execution.action_result.message }}</p>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
